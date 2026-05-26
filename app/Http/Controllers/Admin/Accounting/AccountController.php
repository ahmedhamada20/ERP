<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Accounting\AccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Tree-view index of the chart of accounts.
     */
    public function index(Request $request)
    {
        // Load the whole chart once + sort by code; the view renders the tree recursively.
        $accounts = Account::query()
            ->orderBy('code')
            ->get();

        // Group by parent_id for fast recursive rendering in the view
        $byParent = $accounts->groupBy('parent_id');
        $roots    = $byParent->get('') ?? $byParent->get(null) ?? collect();

        $counts = [
            'total'    => $accounts->count(),
            'active'   => $accounts->where('is_active', true)->count(),
            'groups'   => $accounts->where('is_group', true)->count(),
            'postable' => $accounts->where('is_group', false)->count(),
        ];

        return view('admin.accounting.accounts.index', compact('roots', 'byParent', 'counts'));
    }

    public function create(Request $request)
    {
        $parents = Account::where('is_group', true)->orderBy('code')->get();
        $parentId = $request->query('parent_id');
        $defaultType = $parentId ? optional(Account::find($parentId))->type : null;
        $suggestedCode = Account::suggestNextCode($parentId, $defaultType);

        return view('admin.accounting.accounts.create', compact('parents', 'parentId', 'defaultType', 'suggestedCode'));
    }

    /**
     * AJAX: suggest the next available code under the given parent (or for the given type).
     */
    public function nextCode(Request $request)
    {
        $parentId = $request->query('parent_id') ?: null;
        $type     = $request->query('type') ?: null;

        return response()->json([
            'code' => Account::suggestNextCode($parentId, $type),
        ]);
    }

    public function store(AccountRequest $request)
    {
        $data = $request->validated();
        $data['is_group']  = $request->boolean('is_group');
        $data['is_active'] = $request->boolean('is_active', true);

        Account::create($data);

        return redirect()
            ->route('admin.accounting.accounts.index')
            ->with('success', 'تم إضافة الحساب بنجاح');
    }

    public function edit(Account $account)
    {
        // Exclude self + descendants from possible parents
        $forbiddenIds = $this->collectDescendantIds($account)->push($account->id);
        $parents = Account::where('is_group', true)
            ->whereNotIn('id', $forbiddenIds)
            ->orderBy('code')
            ->get();

        return view('admin.accounting.accounts.edit', compact('account', 'parents'));
    }

    public function update(AccountRequest $request, Account $account)
    {
        $data = $request->validated();
        $data['is_group']  = $request->boolean('is_group');
        $data['is_active'] = $request->boolean('is_active', true);

        // System accounts can't change their type or code — protect the chart's integrity
        if ($account->is_system) {
            unset($data['code'], $data['type']);
        }

        $account->update($data);

        return redirect()
            ->route('admin.accounting.accounts.index')
            ->with('success', 'تم تحديث الحساب بنجاح');
    }

    public function destroy(Account $account)
    {
        if ($account->is_system) {
            return response()->json(['message' => 'لا يمكن حذف حساب من ضمن الحسابات الافتراضية للنظام'], 422);
        }

        if ($account->children()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف حساب له حسابات فرعية. احذف الفروع أولاً.'], 422);
        }

        // TODO (Step 3+): block delete if account has journal lines

        $account->delete();
        return response()->json(['message' => 'تم حذف الحساب']);
    }

    /** Collect all descendant IDs of $account (recursive). */
    private function collectDescendantIds(Account $account): \Illuminate\Support\Collection
    {
        $ids = collect();
        foreach ($account->children as $child) {
            $ids->push($child->id);
            $ids = $ids->merge($this->collectDescendantIds($child));
        }
        return $ids;
    }
}
