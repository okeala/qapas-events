<?php
return [
 'external_enabled'=>(bool)env('EVENTS_PROMOTION_EXTERNAL_ENABLED',false),
 'account_evidence'=>env('EVENTS_PROMOTION_ACCOUNT_EVIDENCE'),
 'facebook_page_id'=>env('EVENTS_FACEBOOK_PAGE_ID'),
 'facebook_page_token'=>env('EVENTS_FACEBOOK_PAGE_TOKEN'),
 'facebook_graph_version'=>env('EVENTS_FACEBOOK_GRAPH_VERSION'),
 'x_user_token'=>env('EVENTS_X_USER_TOKEN'),
 'x_user_id'=>env('EVENTS_X_USER_ID'),
];
