<?php
declare(strict_types=1);

class Qbnox_SMTP_MSGraph_API {
	public static function send_test(
		string $to,
		string $subject,
		string $html,
		string $from
	): bool {

		/** 1️⃣ Circuit breaker gate */
		if (!Qbnox_SMTP_Circuit_Breaker::allow('microsoft')) {
			throw new Exception(
				'Microsoft Graph temporarily disabled due to repeated failures'
			);
		}

		try {

			/** 2️⃣ Retry wrapper */
			Qbnox_SMTP_Retry::run(function () use ($to, $subject, $html, $from) {

				$accessToken = Qbnox_SMTP_OAuth::get_access_token('microsoft');
				if (!$accessToken) {
					throw new Exception('Failed to obtain Microsoft access token');
				}

				$response = wp_remote_post(
					'https://graph.microsoft.com/v1.0/me/sendMail',
					[
						'headers' => [
							'Authorization' => 'Bearer ' . $accessToken,
							'Content-Type'  => 'application/json',
						],
						'body' => wp_json_encode([
							'message' => [
								'subject' => $subject,
								'body' => [
									'contentType' => 'HTML',
									'content'     => $html,
								],
								'toRecipients' => [[
									'emailAddress' => ['address' => $to],
								]],
								'from' => [
									'emailAddress' => ['address' => $from],
								],
							],
						]),
						'timeout' => 15,
					]
				);

				/** Transport-level failure */
				if (is_wp_error($response)) {
					throw new Exception($response->get_error_message());
				}

				/** API-level failure */
				$code = wp_remote_retrieve_response_code($response);
				if ($code !== 202) {
					throw new Exception(
						'Microsoft Graph sendMail failed with HTTP ' . $code
					);
				}
			});

			/** 3️⃣ Success → close breaker */
			Qbnox_SMTP_Circuit_Breaker::success('microsoft');
			return true;

		} catch (\Throwable $e) {

			/** 4️⃣ Failure → open breaker */
			Qbnox_SMTP_Circuit_Breaker::failure('microsoft');
			throw $e;
		}
	}

}

