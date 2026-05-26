<?php

/**
 * Payroll calculation parameters.
 *
 * Override these via .env (preferred) or edit here. The payroll engine reads
 * these once per calculation pass. Numbers are deliberately kept simple
 * defaults — Egyptian rules change yearly and HR should be able to tweak
 * without touching code.
 */
return [

    'social_insurance' => [
        // Employee share of social insurance (% of insurable earnings)
        'employee_rate' => env('PAYROLL_SI_RATE', 11.0),

        // Monthly insurable earnings cap (EGP). Earnings above this aren't
        // subject to SI. The 2024 Egyptian max insurable wage is around
        // 12,600 EGP/month — adjust yearly.
        'monthly_cap'   => env('PAYROLL_SI_CAP', 12600),
    ],

    'income_tax' => [
        // Annual personal exemption (deducted before bracket calc).
        // Egyptian rules: 20k personal + 9k salary = 29k total.
        'annual_exemption' => env('PAYROLL_TAX_EXEMPTION', 29000),

        // Brackets are evaluated on ANNUAL taxable income, then the result
        // is divided by 12 to produce the monthly deduction.
        //
        // Each row: ['upto' => annual ceiling, 'rate' => percent].
        // Use null for the top bracket (no ceiling).
        //
        // Defaults below mirror 2024/2025 Egyptian salary tax brackets.
        'brackets' => [
            ['upto' =>    40000, 'rate' =>  0.0],
            ['upto' =>    55000, 'rate' => 10.0],
            ['upto' =>    70000, 'rate' => 15.0],
            ['upto' =>   200000, 'rate' => 20.0],
            ['upto' =>   400000, 'rate' => 22.5],
            ['upto' =>  1200000, 'rate' => 25.0],
            ['upto' =>     null, 'rate' => 27.5],
        ],
    ],

    'commission' => [
        // Booking statuses eligible for commission. Cancelled and pending
        // bookings never earn commission. Keep this list short — when in
        // doubt, prefer fewer statuses (commissions can be added back, but
        // clawbacks are a pain).
        'eligible_booking_statuses' => ['confirmed', 'in_progress', 'completed'],
    ],

    'absence' => [
        // Daily-rate denominator used for absence deductions.
        // Egyptian convention: divide monthly gross by 30 (not by actual
        // working days, not by 26).
        'days_in_month' => env('PAYROLL_ABSENCE_DAYS', 30),
    ],

    'lateness' => [
        // Hourly-rate denominator = days_in_month × hours_per_day
        // Used to convert lateness_minutes to an EGP deduction.
        'hours_per_day' => env('PAYROLL_LATENESS_HOURS', 8),
    ],

];
