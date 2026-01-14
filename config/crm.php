<?php

return [
    'lead_sources' => [
        'website' => 'Website Form',
        'social_media' => 'Social Media',
        'referral' => 'Referral',
        'email' => 'Email Campaign',
        'paid_ads' => 'Paid Advertising',
    ],
    'scoring_rules' => [
        'weights' => [
            'source' => 0.3,
            'engagement' => 0.4,
            'payment' => 0.3,
        ],
        'thresholds' => [
            'hot' => 80,
            'warm' => 50,
            'cold' => 20,
        ],
    ],
    'auto_assign' => [
        'rules' => [
            'round_robin' => true,
            'territory' => false,
            'skill' => true,
        ],
        'agents' => ['agent1', 'agent2', 'agent3'],
    ],
    'sla_response_hours' => [
        'high' => 1,
        'medium' => 4,
        'low' => 24,
    ],
    'follow_up_sequences' => [
        'new_lead' => ['email_welcome', 'call_intro', 'follow_up'],
        'qualified' => ['demo', 'proposal', 'close'],
    ],
    'b2b_workflow' => [
        'enabled' => true,
        'corporate_discount' => true,
        'bulk_enrollment' => true,
    ],
    'integration' => [
        'endpoints' => [
            'third_party_crm' => 'https://api.thirdparty.com/crm',
            'email_service' => 'https://api.emailservice.com',
        ],
    ],
    'duplication_rules' => [
        'merge_strategy' => 'latest_update',
        'fields_to_merge' => ['name', 'email', 'phone'],
    ],
    'log_decisions' => true,
];
