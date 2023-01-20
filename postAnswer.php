<?php

echo " +-++-++-++-++-++-+ +-++-++-++-++-+ +-++-+ +-++-++-+
Scrape Answers.com and post topics
+-++-++-++-++-++-+ +-++-++-++-++-+ +-++-+ +-++-++-+ \n";

// ----------- Globals ----------------------------------
require 'utilities.php';
require 'simple_html_dom.php';

$path = 'questions/postedQuestions';
$all_files = scandir($path);
$country_files = [];

foreach ($all_files as $file) {
    if (strpos($file, ".txt") !== false) {
        array_push($country_files, $file);
    }
}

$random_file = $country_files[mt_rand(0, count($country_files) - 1)];
$random_slug = str_replace('.txt', '', $random_file);

$random_category_id = -1;

// Repeat till we find a random_category_id
while ($random_category_id == -1) {
    // Get list of country names from random category
    $categories = (json_decode(httpGet("https://discussplaces.com/api/categories?page=" . mt_rand(1, 5)))->categories);
    $master_token = "380588a6-84f5-45fb-b25a-00fd57d762c2";

    echo "Trying to find " . $random_slug . "\n";
    foreach ($categories as $category) {
        $slug = explode("/", $category->slug)[1];

        if ($slug == $random_slug) {
            $random_category_id = $category->cid;
        }
    }
}

$random_category = json_decode(httpGet("https://discussplaces.com/api/category/" . $random_category_id));
$slug = explode("/", $random_category->slug)[1];
$cid = $random_category->cid;
$random_category_name = $random_category->name;

echo $random_category_name . "\n";

if ($random_category_name) {

    // Get exact URL to read

    $posted_questions_file_path = "questions/postedQuestions/" . $random_file;
    $posted_answers_file_path = "questions/postedAnswers/" . $random_file;

    // Open the file to get questionslist
    $posted_answers_list = file_get_contents($posted_answers_file_path, FILE_SKIP_EMPTY_LINES) or file_put_contents($posted_answers_file_path, '');

    $count = 0;

    $posted_questions_list = fopen($posted_questions_file_path, 'r');
    $count = 0;
    $search_url = "";
    while (($line = fgetcsv($posted_questions_list)) !== false) {
        $search_url = $line[0];

        // Skip empty lines
        if (strlen($search_url) >= 10) {
            // Skip already answered questions
            if (stripos($posted_answers_list, $search_url) === false) {

                echo $search_url . "\n";
                $answer_html = file_get_html($search_url);

                // Clear data folder
                $files = glob('../scraper/lib/data/*'); // get all file names
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file);
                    }
                    // delete file
                }

                $files = glob('../scraper/lib/data/captera_files/*'); // get all file names
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file);
                    }
                    // delete file
                }

                $ret = exec("cd ../scraper/lib/; xvfb-run -a node scraper.js " . $search_url . " 2>&1", $out, $err);
                //    $ret = exec("cd ../scraper/lib/; node scraper.js " . $search_url . " 2>&1", $out, $err);

                echo $ret . "\n";

                if ($ret == "success") {

                    $count++;
                    $url = '../scraper/lib/data/captera.html';
                    echo "Looking into " . $search_url . "\n";
                    $html = file_get_html($url);

                    if ($html != null) {

                        $question = "";
                        $content = "";

                        // Find the questions
                        foreach ($html->find('h1') as $element) {
                            $question = $element->plaintext . "\n";
                        }

                        // Find the answer
                        foreach ($html->find('meta') as $element) {
                            // if ($element->find('name') == "description") {
                            foreach ($element->find('meta[name=description]') as $vals) {
                                $content = $vals->content . "\n";
                                $content = str_replace('&#39;', "", $content);
                                $content = str_replace('&quot;', "", $content);
                            }
                        }

                        echo $question . "\n";
                        echo $content . "\n";

                        $search_term = $question;

                        if (strlen($content) >= 10) {
                            if (createPost($cid, $question, $content, $search_term)) {
                                echo "Saving information \n";
                            } else {
                                echo "Problem saving the post" . "\n";
                            }
                            // Update the log in anycase
                            $posted_answers_list .= $search_url . "\n";
                            file_put_contents($posted_answers_file_path, $posted_answers_list);
                        } else {
                            echo "ending_paragraph->plaintext " . "\n";
                        }
                    } else {
                        echo "Page is empty \n";
                    }
                } else {
                    echo "No Success" . "\n";
                }

                $count++;
            }

            if ($count >= 1) {
                break;
            }
        }
    }
}
