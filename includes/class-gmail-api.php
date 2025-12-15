<?php
declare(strict_types=1);

class Qbnox_SMTP_Gmail_API {

	public function send_test(array $email): bool {
		/** 1️⃣ Circuit breaker check */
		if (!Qbnox_SMTP_Circuit_Breaker::allow('google')) {
			throw new Exception(
				'Gmail API temporarily disabled (too many failures)'
			);
		}

		try {

			/** 2️⃣ Retry wrapper */
			Qbnox_SMTP_Retry::run(function () use ($email) {

				$accessToken = $this->get_access_token();

				$response = wp_remote_post(
					'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
					[
						'headers' => [
							'Authorization' => 'Bearer ' . $accessToken,
							'Content-Type'  => 'application/json',
						],
						'body' => json_encode([
							'raw' => $this->build_raw_message($email),
						]),
						'timeout' => 15,
					]
				);

				if (is_wp_error($response)) {
					throw new Exception($response->get_error_message());
				}

				$code = wp_remote_retrieve_response_code($response);
				if ($code >= 500) {
					throw new Exception('Gmail API server error ' . $code);
				}

			});

			/** 3️⃣ Success */
			Qbnox_SMTP_Circuit_Breaker::success('google');
			return true;

		} catch (\Throwable $e) {

			/** 4️⃣ Failure */
			Qbnox_SMTP_Circuit_Breaker::failure('google');
			throw $e;
		}
	}

	/*
    public static function send_test(
        string $to,
        string $subject,
        string $html,
        string $from
    ): bool {

        $accessToken = Qbnox_SMTP_OAuth::get_access_token('google');
        if (!$accessToken) {
            throw new Exception('Failed to obtain Google access token');
        }

        $raw = "From: {$from}\r\n";
        $raw .= "To: {$to}\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $raw .= $html;

        $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $response = wp_remote_post(
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'body' => wp_json_encode([
                    'raw' => $encoded,
                ]),
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        return wp_remote_retrieve_response_code($response) === 200;
    }
	 */
}
