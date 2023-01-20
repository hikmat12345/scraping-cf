<?php

echo " +-++-++-++-++-++-+ +-++-++-++-++-+ +-++-+ +-++-++-+
Scrape askinglot.com questions from seed urls
+-++-++-++-++-++-+ +-++-++-++-++-+ +-++-+ +-++-++-+ \n";

// ----------- Globals ----------------------------------
require '../../utilities.php';
require '../../simple_html_dom.php';
require '../../vendor/autoload.php';

use League\HTMLToMarkdown\HtmlConverter;
$dotenv = Dotenv\Dotenv::createImmutable('../../');
$dotenv->load();
$converter = new HtmlConverter(array('strip_tags' => true, 'use_autolinks' => false, 'hard_break' => true));


$master_token = "380588a6-84f5-45fb-b25a-00fd57d762c2";


$slug = "united-states";
$cid = 244;
$random_category_name = "United States";
$url = "https://www.officeholidays.com/countries/" . $slug;

$csv_file_path = 'askinglot.com.txt';
$already_inserted_words_file_path = 'askinglot.com-inserted-question.txt';

// Open the file to get existing content
$already_inserted_words_file = file_get_contents($already_inserted_words_file_path, FILE_SKIP_EMPTY_LINES);
  $csv_file = fopen($csv_file_path, 'r');
  $count = 0;
  $url = ""; 
  while ((   $line = fgetcsv($csv_file)  )   !== FALSE) {
      $url = $line[0];
     
   if (stripos($already_inserted_words_file, $url) === FALSE) {
    $files = glob('../../scraper/data/*'); // get all file names
      echo $url;
    foreach ($files as $file) { // iterate files
    if (is_file($file)) {
        unlink($file);
    }
    // delete file
    } 
    $files = glob('../../scraper/data/captera_files/*'); // get all file names
      foreach ($files as $file) { // iterate files
        if (is_file($file)) {
          unlink($file);
        }
        // delete file
      } 
      
      if ($_ENV['ENV'] == "DEV") { 
           // This is to run the scraper with a GUI based scrapper
           $ret = exec("cd .. && cd .. && cd scraper && node  reader.js " . escapeshellcmd($url));
        } else {
            // This is to run scraper on Linux
            $ret = exec("cd ../../scraper/; xvfb-run -a node reader.js " . escapeshellcmd($url));
        }

      echo $ret . "\n";

  if ($ret == "success") {
       $filePathurl = "../../scraper/data/captera.html"; 
       $myhtml = file_get_html($filePathurl);  
      
          $title = $myhtml->find('h1')[0]->plaintext;
          echo $title;
          $title = trim($title);
          $title = str_replace('&#039;', "", $title);
  
          $content = $myhtml->find('.answer')[0]->children(0); 
          // Convert to markdown
          $content = $converter->convert($content);
           echo  $content;
          $first_post_title = $title;
          if (strlen($content) > 10) {
              $first_post_content = $content;
          } else {
              $first_post_content = $title;
          }
          $search_term = $first_post_title;
  
          $tags = "";
          foreach ($myhtml->find('.main_question .badge') as $e) {
              $tags .= "&tags[]=" . $e->innertext;
          }
  
          echo "Checking before posting -------------- \n" . $title . "\n";
  
          if (strlen($title) > 0) {
  
               echo "---dddd---\n" . $tags . "\n-----\n";
  
              if (createTopic(ucfirst(strtolower($random_category_name)), $cid, $first_post_title, $first_post_content, $first_post_title, $first_post_content, $search_term, $tags)) {
                  echo "Saving information " . $first_post_title . "\n";
                  //       echo $first_post_content . "\n";
  
                  $already_inserted_words_file .= $url . "\n";
                  // Adding to log
                  file_put_contents($already_inserted_words_file_path, $already_inserted_words_file);
  
                  $count++;
  
                  if ($count >= 1) {
                      break;
                  }
  
                  echo "\n Count : ". $count . "\n";
  
              } else {
                  $already_inserted_words_file .= $url . "\n";
                  // Adding to log
                  try {
                      file_put_contents($already_inserted_words_file_path, $already_inserted_words_file);
                  } catch (\Throwable $th) {
                      echo $th . "Can't save \n";
                  }
              }
          }
      } else {
            echo "Not able to scrape \n";
            break;
      }
    }
  }