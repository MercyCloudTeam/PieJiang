#!/bin/bash
mkdir -p /ssl
curl "{!! route('api.server.cert.key', ['server' => $server->id, 'token' => $server->token, 'download' => true]) !!}" > /ssl/cert.key
curl "{!!route('api.server.cert', ['server' => $server->id, 'token' => $server->token, 'download' => true])!!}" > /ssl/cert.pem
chmod -R 777 /ssl
mkdir -p /scripts
echo -n "Server or Access? [s/a]:"
read mode
if [ "$mode" = "s" ]; then
curl "{!!route('api.server.xray.config', ['server' => $server->id, 'token' => $server->token, 'download' => true])!!}" >  /usr/local/etc/xray/config.json
echo "#!/bin/bash" > /scripts/xray-config-updare.sh
echo "curl \"{!!route('api.server.xray.config', ['server' => $server->id, 'token' => $server->token, 'download' => true])!!}\" >  /usr/local/etc/xray/config.json" >> /scripts/xray-config-updare.sh
echo "sleep 1" >> /scripts/xray-config-updare.sh
echo "service xray restart" >> /scripts/xray-config-updare.sh
echo "sleep 1" >> /scripts/xray-config-updare.sh
echo "service xray status" >> /scripts/xray-config-updare.sh
chmod +x /scripts/xray-config-updare.sh
elif [ "$mode" = "a" ]; then
curl "{!!route('api.server.xray.config.access', ['server' => $server->id, 'token' => $server->token, 'download' => true])!!}" >  /usr/local/etc/xray/config.json
echo "#!/bin/bash" > /scripts/xray-config-updare.sh
echo "curl \"{!!route('api.server.xray.config.access', ['server' => $server->id, 'token' => $server->token, 'download' => true])!!}\" >  /usr/local/etc/xray/config.json" >> /scripts/xray-config-updare.sh
echo "sleep 1" >> /scripts/xray-config-updare.sh
echo "service xray restart" >> /scripts/xray-config-updare.sh
echo "sleep 1" >> /scripts/xray-config-updare.sh
echo "service xray status" >> /scripts/xray-config-updare.sh
chmod +x /scripts/xray-config-updare.sh
fi
sleep 1
service xray restart
sleep 5
service xray status

echo "Done! PieJiang Love You!"
