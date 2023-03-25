<?php

namespace App\Console\Commands;

use App\Models\ProxyGroup;
use App\Models\Rule;
use Illuminate\Console\Command;

class ImportRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piejiang:import-rules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import rules from storage/app/rules to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $list = scandir(storage_path('app/rules'));
        $list = array_diff($list, ['.', '..']);

        foreach ($list as $file) {
            //print file name
            $this->info($file);

            $groupName = str_replace('.list', '', $file);
            ProxyGroup::firstOrCreate([
                'name' => $groupName,
                'type' => 'select'
            ]);

            //parse file
            $content = file_get_contents(storage_path('app/rules/' . $file));
            $content = preg_replace('/^#.*$/m', '', $content);
            $content = preg_replace('/^\s*$/m', '', $content);
            $content = preg_replace('/\r\n/', '', $content);
            $array = explode(PHP_EOL, $content);
            $array = array_diff($array, ['']);
            foreach ($array as $rule) {
                $rule = explode(',', $rule);
                Rule::firstOrCreate([
                    'content' => $rule[1],
                    'type' => $rule[0],
                    'proxy_group' => $groupName,
                    'resolve' => empty($rule[2])
                ]);
            }
        }
    }
}
