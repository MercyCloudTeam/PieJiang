<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Badcow\DNS\Classes;
use Badcow\DNS\Zone;
use Badcow\DNS\Rdata\Factory;
use Badcow\DNS\ResourceRecord;
use Badcow\DNS\AlignedBuilder;
use App\Models\Server;

class DNSController extends Controller
{
    public function randDomain(Request $request)
    {
        // dd(env('RAND_DOMAIN'));
        $zone = new Zone(env('RAND_DOMAIN').".");
        // $zone = new Zone("test.com.");
        $zone->setDefaultTtl(3600);

        //SOA
        $soa = new ResourceRecord;
        $soa->setName('@');
        $soa->setRdata(Factory::SOA(
            'ns1.'.env('RAND_DOMAIN').'.',
            'admin.'.env('RAND_DOMAIN').'.',
            date('Ymd01'),
            3600,
            600,
            604800,
            3600
        ));
        $zone->addResourceRecord($soa);

        //NS
        $ns = new ResourceRecord;
        $ns->setName('@');
        $ns->setRdata(Factory::NS('ns1.'.env('RAND_DOMAIN').'.'));
        $zone->addResourceRecord($ns);

        //A
        $a = new ResourceRecord;
        $a->setName('ns1.'.env('RAND_DOMAIN').'.');
        $a->setRdata(Factory::A($request->get('ip') ?? $request->ip()));
        $zone->addResourceRecord($a);



        //get servers domain
        $servers = Server::all()->toArray();
        // dd($servers);
        foreach($servers as $server){
            $a = new ResourceRecord;
            $a->setName(str_replace(".".env('RAND_DOMAIN'), '', $server['domain']));
            $a->setRdata(Factory::A($server['ip']));
            $a->setComment("Server ID ".$server['id']);
            $zone->addResourceRecord($a);
            // $zone->addResourceRecord(new ResourceRecord($server['domain'], Factory::A($server['ip'])));
        }

        $builder = new AlignedBuilder();
        return response($builder->build($zone))->header('Content-Type', 'text/plain');
    }
}
