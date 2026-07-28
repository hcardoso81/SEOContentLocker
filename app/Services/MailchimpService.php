<?php
namespace SeoContentLocker\Services;

if (!defined('ABSPATH')) exit;

class MailchimpService
{
    public function subscribe($email, $firstName, $slug, $originTag = 'HOME')
    {
        $apiKey = get_option('seocontentlocker_mc_api_key');
        $listId = get_option('seocontentlocker_mc_list_id');

        $dc = substr($apiKey, strpos($apiKey, '-') + 1);
        $subscriberHash = md5(strtolower($email));
        $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";

        $dynamicTag = $this->resolveContentTag($slug);

        $body = [
            'email_address' => $email,
            'status_if_new' => 'subscribed',
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => $firstName,
            ],
        ];

        $response = wp_remote_request($url, [
            'method' => 'PUT',
            'headers' => [
                'Authorization' => 'apikey ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            log_error(
                'Mailchimp request transport error',
                'mailchimp_transport',
                [
                    'email' => $email,
                    'error' => $response->get_error_message(),
                ]
            );

            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $bodyResponse = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 && $code !== 201) {
            log_error(
                'Mailchimp API rejected subscription',
                'mailchimp_api',
                [
                    'email' => $email,
                    'status_code' => $code,
                    'response' => $bodyResponse,
                ]
            );

            return [
                'success' => false,
                'error' => $bodyResponse['detail'] ?? 'Unknown error',
                'code' => $code,
            ];
        }

        log_mailchimp_success(
            $email,
            $bodyResponse['status'] ?? 'subscribed',
            $code
        );

        $tagsUrl = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}/tags";
        $tags = [
            [
                'name' => 'SUSCRIPTION_SYSTEM',
                'status' => 'active',
            ],
            [
                'name' => in_array($originTag, ['HOME', 'LANDING'], true) ? $originTag : 'HOME',
                'status' => 'active',
            ],
        ];

        if ($dynamicTag) {
            $tags[] = [
                'name' => $dynamicTag,
                'status' => 'active',
            ];
        }

        $tagsResponse = wp_remote_post($tagsUrl, [
            'headers' => [
                'Authorization' => 'apikey ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode(['tags' => $tags]),
        ]);

        if (is_wp_error($tagsResponse)) {
            return [
                'success' => false,
                'error' => 'Contact added but tagging failed: ' . $tagsResponse->get_error_message(),
                'code' => $code,
            ];
        }

        return [
            'success' => true,
            'status' => $bodyResponse['status'] ?? 'subscribed',
            'code' => $code,
            'tag_applied' => $dynamicTag,
        ];
    }

    private function resolveContentTag($slug)
    {
        if (!$slug) {
            return null;
        }

        $post = get_page_by_path($slug, OBJECT, ['post', 'page']);

        if (!$post) {
            return null;
        }

        if ($post->post_type === 'post') {
            return 'ARTICLE';
        }

        if ($post->post_type === 'page') {
            return 'NEWSLETTER';
        }

        return null;
    }
}
