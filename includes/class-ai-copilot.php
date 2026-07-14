<?php
/**
 * AI Copilot Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_AI_Copilot {

	public static function generate( $prompt, $context = '' ) {
		$enabled = get_option( 'an_ai_enabled', 'no' ) === 'yes';
		$provider = get_option( 'an_ai_provider', 'local' );
		$model = get_option( 'an_ai_model', 'gpt-4o' );

		if ( ! $enabled ) {
			return self::local_fallback( $prompt, $context );
		}

		switch ( $provider ) {
			case 'openai':
				$api_key = get_option( 'an_openai_key' );
				if ( ! empty( $api_key ) ) {
					$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
						'headers' => [
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $api_key
						],
						'body'    => json_encode( [
							'model'       => $model ? $model : 'gpt-4o',
							'messages'    => [
								[ 'role' => 'system', 'content' => 'You are a professional agency operations copilot. Assist with ' . $context ],
								[ 'role' => 'user', 'content' => $prompt ]
							],
							'temperature' => 0.7
						] ),
						'timeout' => 15
					] );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$body = json_decode( wp_remote_retrieve_body( $response ), true );
						if ( isset( $body['choices'][0]['message']['content'] ) ) {
							return trim( $body['choices'][0]['message']['content'] );
						}
					}
				}
				break;

			case 'gemini':
				$api_key = get_option( 'an_gemini_key' );
				if ( ! empty( $api_key ) ) {
					$model_name = $model ? $model : 'gemini-pro';
					$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_name}:generateContent?key=" . $api_key;
					$response = wp_remote_post( $url, [
						'headers' => [ 'Content-Type' => 'application/json' ],
						'body'    => json_encode( [
							'contents' => [
								[
									'parts' => [
										[ 'text' => $prompt . "\n\nContext details: " . $context ]
									]
								]
							]
						] ),
						'timeout' => 15
					] );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$body = json_decode( wp_remote_retrieve_body( $response ), true );
						if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
							return trim( $body['candidates'][0]['content']['parts'][0]['text'] );
						}
					}
				}
				break;

			case 'claude':
				$api_key = get_option( 'an_claude_key' );
				if ( ! empty( $api_key ) ) {
					$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
						'headers' => [
							'Content-Type'      => 'application/json',
							'x-api-key'         => $api_key,
							'anthropic-version' => '2023-06-01'
						],
						'body'    => json_encode( [
							'model'     => $model ? $model : 'claude-3-opus-20240229',
							'max_tokens'=> 1000,
							'messages'  => [
								[ 'role' => 'user', 'content' => $prompt . "\n\nContext: " . $context ]
							]
						] ),
						'timeout' => 15
					] );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$body = json_decode( wp_remote_retrieve_body( $response ), true );
						if ( isset( $body['content'][0]['text'] ) ) {
							return trim( $body['content'][0]['text'] );
						}
					}
				}
				break;
		}

		// Fallback to offline / mock generator
		return self::local_fallback( $prompt, $context );
	}

	private static function local_fallback( $prompt, $context ) {
		// Beautiful, context-aware offline mock generation for offline/free tier solo-agencies
		if ( strpos( strtolower($context), 'time_suggest' ) !== false || strpos( strtolower($prompt), 'time block' ) !== false ) {
			return json_encode([
				[
					'title' => 'Deep Work: Core Engineering',
					'desc'  => 'Your mental energy is peak between 9am-12pm. Dedicate this block to high-complexity development.',
					'type'  => 'deep_work',
					'start' => date('Y-m-d 09:00:00'),
					'end'   => date('Y-m-d 11:30:00'),
					'icon'  => '💻'
				],
				[
					'title' => 'Admin & Client Sync',
					'desc'  => 'Batch administrative reviews, responses, and social hub messages in the afternoon.',
					'type'  => 'shallow_work',
					'start' => date('Y-m-d 14:00:00'),
					'end'   => date('Y-m-d 15:00:00'),
					'icon'  => '📩'
				],
				[
					'title' => 'Strategic Recharge',
					'desc'  => 'Mandatory offline break to avoid burnout and keep productivity levels sustainable.',
					'type'  => 'break',
					'start' => date('Y-m-d 11:30:00'),
					'end'   => date('Y-m-d 12:00:00'),
					'icon'  => '🍹'
				]
			]);
		}

		if ( strpos( strtolower($context), 'content_batch' ) !== false || strpos( strtolower($prompt), 'batch' ) !== false ) {
			return "How to Scale a Solo Freelance Agency to 6-Figures\nReduce Client Friction with Automated Portal Workflows\nSlaying the Overhead Beast: Why Tool Consolidation is Key";
		}

		if ( strpos( strtolower($context), 'proposal' ) !== false || strpos( strtolower($context), 'scope' ) !== false ) {
			return "### 🚀 Project Scope & Strategic Proposal\n\n**Prepared for:** Potential Client\n**Goal:** Launching a high-performance web platform and conversion funnel to achieve 3x lead volume.\n\n#### 1. Core Deliverables\n- **Responsive Web Platform:** Branded, responsive layout with modular Inter design system.\n- **Sales Funnel Integration:** Connecting landing pages with automated Lead Capture.\n- **True ROI Dashboard Tracking:** Enabling automated profit tracking.\n\n#### 2. Pricing & Investment\n- **Project Fee:** $5,000.00\n- **Buffer Period:** 5 Days\n\n*Generated securely via Nexus AI Copilot.*";
		}

		if ( strpos( strtolower($context), 'message' ) !== false ) {
			return "Hi there! Thank you for reaching out. I have reviewed your request, and our agency team will get back to you with the draft deliverables shortly. Let us know if you need anything else in the meantime!";
		}

		return "AI Copilot Response: Based on your prompt '" . esc_html($prompt) . "', we suggest optimizing your agency operations, consolidating your tooling, and automating client communication via Agency Nexus dashboards.";
	}
}
