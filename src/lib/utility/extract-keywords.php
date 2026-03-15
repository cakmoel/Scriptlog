<?php 

/**
 * extractKeywords
 *
 * @category function to extract keywords from content
 * @author Nirmala Khanza
 * @license MIT
 * @version 1.0
 * @param string $content
 * @param integer $limit
 * 
 */
function extract_keywords($content, $limit = 5)
{
    $content = strip_tags($content);
    $words = str_word_count(strtolower($content), 1);
    $wordCounts = array_count_values($words);
    arsort($wordCounts);

    $commonWords = ['the', 'and', 'that', 'for', 'with', 'this', 'your', 'are', 'have', 'from', 'dan', 'di'];
    $keywords = array_diff_key($wordCounts, array_flip($commonWords));

    return array_slice(array_keys($keywords), 0, $limit);
}