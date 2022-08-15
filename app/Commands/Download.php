<?php

namespace App\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\File;
class Download extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'download {memories-file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('memories-file');
        $file_name = basename($file);
        $file_data = file_get_contents($file);
        $is_json = Str::isJson($file_data);
        if (!$is_json) {
            $this->error("File don't content json data");
        }
        $data_array = json_decode($file_data,true,512);
        $d = Arr::pluck($data_array['Saved Media'],'Date');
        if (isset($data_array['Saved Media']) and $files_array = $data_array['Saved Media']) {
            $files_count = count($files_array);
            $this->info('Start downloading ' . $files_count . ' File');
            $bar = $this->output->createProgressBar($files_count);
            $download_dir =  $this->CreateFolder($file_name);
            $files_array =  Arr::sortRecursive($files_array);
            foreach ($files_array as $index => $file_to_download) {
                $download_link = $file_to_download['Download Link'];
                $date = $file_to_download['Date'];
                $this->downloadFile($index,$download_link,$date,$download_dir);
                $bar->advance();
            }
            $bar->finish();
        } else {
            $this->error('Saved Media dont exist on the file');
        }


    }

    public function downloadFile($index,$link,$date,$download_dir)
    {
        $path_name = $download_dir.'/'.$index.'_'.Carbon::parse($date)->timestamp;
        if (count(glob($path_name.'.*'))){
            return false;
        }
        $client = new Client();
        $response =  $client->post($link);
        $link = $response->getBody()->getContents();
        $file =  file_get_contents($link);
        file_put_contents($path_name,$file);
        $mime_type = mime_content_type($path_name);
        $extension = explode('/', $mime_type )[1];
        rename($path_name,$path_name.'.'.$extension);
    }
    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    public function CreateFolder($filename)
    {
        $file_path = getcwd().'/'.str_replace('.json','',$filename);
       $exist = file_exists($file_path);
       if (!$exist){
           mkdir($file_path,0777,true);
       }
       return $file_path;


    }

    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
