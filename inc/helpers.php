<?php
/**
 * Helper functions for Cheshire Cat Chatbot
 *
 * @package CheshireCatChatbot
 */

namespace webgrafia\cheshirecat;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get predefined responses with post override support.
 *
 * This function checks if there are post-specific overrides for predefined responses
 * and returns those if available, otherwise falls back to the global settings.
 * If a maximum number of questions is set, it will randomize and limit the responses.
 *
 * @since 0.7.1
 * @param int $post_id The post ID to check for overrides.
 * @param bool $is_product_category Whether the current page is a product category page.
 * @param int $product_category_id The product category ID if on a product category page.
 * @return array The predefined responses as an array.
 */
function cheshirecat_get_predefined_responses_with_override( $post_id = 0, $is_product_category = false, $product_category_id = 0 ) {
    $responses = array();

    // Get the maximum number of predefined questions to show
    $max_questions = absint( get_option( 'cheshire_plugin_max_predefined_questions', 0 ) );

    // Helper function to randomize and limit responses
    $process_responses = function( $resp ) use ( $max_questions ) {
        // If max_questions is 0 or there are fewer responses than max_questions, return all responses
        if ( $max_questions === 0 || count( $resp ) <= $max_questions ) {
            return $resp;
        }

        // Randomize the responses
        shuffle( $resp );

        // Return only the first max_questions responses
        return array_slice( $resp, 0, $max_questions );
    };

    // Check if we have a post ID and if it has overrides
    if ( $post_id > 0 ) {
        $post_specific_responses = get_post_meta( $post_id, '_cheshire_predefined_responses', true );

        // If post has specific responses, use those
        if ( ! empty( $post_specific_responses ) ) {
            // Split by newline and filter out empty lines
            $responses = array_filter( explode( "\n", $post_specific_responses ), 'trim' );

            // If we have responses, process and return them
            if ( ! empty( $responses ) ) {
                return $process_responses( $responses );
            }
        }

        // If no post-specific responses, check if this is a WooCommerce product with category responses
        if ( function_exists( 'get_the_terms' ) ) {
            $product_categories = get_the_terms( $post_id, 'product_cat' );

            if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) {
                $top_level_category_ids = array();

                foreach ( $product_categories as $cat ) {
                    if ( $cat->parent == 0 ) {
                        $top_level_category_ids[] = $cat->term_id;
                    } else {
                        $ancestors = get_ancestors( $cat->term_id, 'product_cat', 'taxonomy' );
                        if ( ! empty( $ancestors ) ) {
                            // The last element in the ancestors array is the highest-level (root) ancestor
                            $top_level_category_ids[] = end( $ancestors );
                        } else {
                            $top_level_category_ids[] = $cat->term_id;
                        }
                    }
                }

                $top_level_category_ids = array_unique( $top_level_category_ids );

                foreach ( $top_level_category_ids as $top_cat_id ) {
                    $category_specific_responses = get_term_meta( $top_cat_id, '_cheshire_predefined_responses', true );

                    if ( ! empty( $category_specific_responses ) ) {
                        $responses = array_filter( explode( "\n", $category_specific_responses ), 'trim' );

                        if ( ! empty( $responses ) ) {
                            return $process_responses( $responses );
                        }
                    }
                }
            }
        }
    }

    // Check if we're in a product category archive
    if ( $is_product_category && $product_category_id > 0 ) {
        // We have product category information from the parameters

        // Check if this category has specific responses
        $category_specific_responses = get_term_meta( $product_category_id, '_cheshire_predefined_responses', true );

        // If category has specific responses, use those
        if ( ! empty( $category_specific_responses ) ) {
            // Split by newline and filter out empty lines
            $responses = array_filter( explode( "\n", $category_specific_responses ), 'trim' );

            // If we have responses, process and return them
            if ( ! empty( $responses ) ) {
                return $process_responses( $responses );
            }
        }

        // If no category-specific responses, try to use the global product category responses
        $global_product_category_responses = get_option( 'cheshire_plugin_product_category_predefined_responses', '' );

        if ( ! empty( $global_product_category_responses ) ) {
            // Split by newline and filter out empty lines
            $responses = array_filter( explode( "\n", $global_product_category_responses ), 'trim' );

            // If we have responses, process and return them
            if ( ! empty( $responses ) ) {
                return $process_responses( $responses );
            }
        }
    }

    // Fall back to global responses if no post-specific or category-specific ones or if they're empty
    $global_responses = get_option( 'cheshire_plugin_predefined_responses', '' );

    if ( ! empty( $global_responses ) ) {
        // Split by newline and filter out empty lines
        $responses = array_filter( explode( "\n", $global_responses ), 'trim' );
    }

    // Process and return the responses
    return $process_responses( $responses );
}
