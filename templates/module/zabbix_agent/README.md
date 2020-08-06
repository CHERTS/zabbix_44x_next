
# Template Module Zabbix agent

## Overview

For Zabbix version: 4.4  

## Setup


## Zabbix configuration


### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$AGENT.TIMEOUT}|<p>Timeout after which agent is considered unavailable. Works only for agents reachable from Zabbix server/proxy (passive mode).</p>|`3m`|

## Template links

There are no template links in this template.

## Discovery rules


## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|Monitoring_agent|Version of Zabbix agent running|<p>-</p>|ZABBIX_PASSIVE|agent.version<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Monitoring_agent|Host name of Zabbix agent running|<p>-</p>|ZABBIX_PASSIVE|agent.hostname<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Monitoring_agent|Version of Zabbix agent running|<p>-</p>|ZABBIX_PASSIVE|agent.version<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Monitoring_agent|Zabbix agent ping|<p>The agent always returns 1 for this item. It could be used in combination with nodata() for availability check.</p>|ZABBIX_PASSIVE|agent.ping|
|Status|Zabbix agent availability|<p>Monitoring agent availability status</p>|INTERNAL|zabbix[host,agent,available]|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|Zabbix agent is not available (for {$AGENT.TIMEOUT})|<p>For passive only agents, host availability is used with {$AGENT.TIMEOUT} as time threshold.</p>|`{TEMPLATE_NAME:zabbix[host,agent,available].max({$AGENT.TIMEOUT})}=0`|AVERAGE|<p>Manual close: YES</p>|

## Feedback

Please report any issues with the template at https://support.zabbix.com

# Template Module Zabbix agent active

## Overview

For Zabbix version: 4.4  

## Setup


## Zabbix configuration


### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$AGENT.NODATA_TIMEOUT}|<p>No data timeout for active agents. Consider to keep it relatively high.</p>|`30m`|

## Template links

There are no template links in this template.

## Discovery rules


## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|Monitoring_agent|Version of Zabbix agent running|<p>-</p>|ZABBIX_ACTIVE|agent.version<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Monitoring_agent|Host name of Zabbix agent running|<p>-</p>|ZABBIX_ACTIVE|agent.hostname<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Monitoring_agent|Version of Zabbix agent running|<p>-</p>|ZABBIX_ACTIVE|agent.version<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Status|Zabbix agent ping|<p>The agent always returns 1 for this item. It could be used in combination with nodata() for availability check.</p>|ZABBIX_ACTIVE|agent.ping|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|Zabbix agent is not available (or nodata for {$AGENT.NODATA_TIMEOUT})|<p>For active agents, nodata() with agent.ping is used with {$AGENT.NODATA_TIMEOUT} as time threshold.</p>|`{TEMPLATE_NAME:agent.ping.nodata({$AGENT.NODATA_TIMEOUT})}=1`|AVERAGE|<p>Manual close: YES</p>|

## Feedback

Please report any issues with the template at https://support.zabbix.com

