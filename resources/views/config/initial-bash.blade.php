#!/bin/bash

apt install curl wget net-tools -y

mkdir -p /ssl

curl "{!! route('api.server.cert.key', ['server' => $server->id, 'token' => $server->token, 'download' => true]) !!}" > /ssl/cert.key
curl "{!!route('api.server.cert', ['server' => $server->id, 'token' => $server->token, 'download' => true])!!}" > /ssl/cert.pem

chmod -R 777 /ssl
mkdir -p /scripts

curl "{!! route('ca') !!}" > /usr/local/share/ca-certificates/piejiang.crt
update-ca-certificates

echo "
#Piejiang Script Addons Start
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 30
net.ipv4.icmp_ignore_bogus_error_responses = 1
net.ipv4.tcp_syn_retries = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv4.tcp_wmem = 30000000 30000000 30000000
net.ipv4.ip_local_port_range = 1024 65000
net.core.optmem_max = 10000000
net.core.rmem_default = 10000000
net.core.rmem_max = 10000000
net.core.default_qdisc=fq
net.ipv4.tcp_congestion_control=bbr
#Script Addons End
" >> /etc/sysctl.conf

sysctl -p

echo -n "Install Xray [y/N]:"
read install_xray
if [ "$install_xray" = "y" ]; then
bash -c "$(curl -L https://github.com/XTLS/Xray-install/raw/main/install-release.sh)" @ install --beta
fi

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
