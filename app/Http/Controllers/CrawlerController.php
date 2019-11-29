<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use App\Crawler;

class CrawlerController extends Controller
{
    public function index() {
        //Gets all not crawled links in the database
        $linksDB = Crawler::select('url')->get();

        $links = [];
        $queue = [];
        foreach ($linksDB as $link) {
            array_push($links, $link->url);

            //Mount the first queue
            if(!$link->crawled) {
                array_push($queue, $link->url);
            }
        };

        //Checks if there is a link
        //If there is not, gets a default one
        $links = (count($links) > 0) ? $links : ['https://guiadacozinha.com.br/receitas/pernil-na-cachaca/'];
        $queue = (count($queue) > 0) ? $queue : $links;

        //Starts the crawler
        $client = new Client();
        $level = 0;

        //Do the crawling
        while($level < 2) {
            $__links = [];
            foreach($queue as $link) {
                $crawler = $client->request('GET', $link);
    
                $_links = $crawler->filter('.receitas-imagem a')->each(function($el) {
                    return $el->link()->getUri();
                });

                $__links = array_unique(array_merge($__links, $_links));
            }
            
            //Creates the new queue
            $queue = array_filter($__links, function($item) use($links) {
                return !in_array($item, $links);
            });

            $links = array_unique(array_merge($links, $__links));
            $level++;
        }

        //Saves the results to the database
        foreach ($links as $link) {
            $dbLink = Crawler::where('url', $link)->first();
            if($dbLink == []) {
                Crawler::create([
                    'url' => $link,
                    'crawled' => !in_array($link, $queue)
                ]);
            } else {
                $dbLink->update([
                    'crawled' => !in_array($link, $queue)
                ]);
            }
        }


        return response()->json([
            'links_count' => count($links),
            'links' => $links
        ]);
    }
}
