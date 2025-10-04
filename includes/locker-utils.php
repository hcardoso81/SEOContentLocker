<?php
function seocontentlocker_mailchimp_subscribe($apiKey, $listId, $email) {
    $dc = substr($apiKey, strpos($apiKey, '-')+1); // datacenter de Mailchimp
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/";

    $body = [
        'email_address' => $email,
        'status'        => 'pending' // Double opt-in
    ];

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'apikey ' . $apiKey,
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode($body)
    ]);

    if (is_wp_error($response)) {
        return ['success' => false, 'error' => $response->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($response);
    return ['success' => ($code == 200 || $code == 201)];
}
