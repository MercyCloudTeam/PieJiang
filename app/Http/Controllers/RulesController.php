<?php

namespace App\Http\Controllers;

use App\Models\ProxyGroup;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class RulesController extends Controller
{
    public function proxyGroups(ProxyGroup $proxyGroup)
    {
        $rules = $proxyGroup->rules;
        return Inertia::render('ProxyGroups/Show', [
            'proxyGroup' => $proxyGroup,
            'rules' => $rules,
        ]);
    }

    public function proxyGroupsDelete(ProxyGroup $proxyGroup)
    {
        Rule::where('proxy_group', $proxyGroup->name)->delete();
        $proxyGroup->delete();
        return Redirect::route('proxies.index');
    }


    public function proxyGroupsStore(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
        ]);
        ProxyGroup::create([
            'name' => $request->name,
            'type' => 'select',
        ]);
        return Redirect::route('proxies.index');
    }

    public function ruleStore(Request $request, ProxyGroup $proxyGroup)
    {
        $this->validate($request, [
//            'name' => 'required|string',
            'type' => 'required|string',
            'content' => 'required|string',
            'resolve' => 'nullable|boolean',
        ]);
        Rule::create([
            'proxy_group' => $proxyGroup->name,
            'content' => $request['content'],
            'resolve' => $request['resolve'] ?? false,
            'type' => $request['type'],
        ]);
        return back();

    }


    public function ruleDestroy(Rule $rule)
    {
        $rule->delete();
        return back();
    }
}
