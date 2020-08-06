
# Template Net D-Link DES 7200 SNMPv2

## Overview

For Zabbix version: 4.4  

## Setup


## Zabbix configuration


### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$CPU.UTIL.CRIT}|<p>-</p>|`90`|
|{$FAN_CRIT_STATUS}|<p>-</p>|`5`|
|{$MEMORY.UTIL.MAX}|<p>-</p>|`90`|
|{$PSU_CRIT_STATUS}|<p>-</p>|`5`|
|{$TEMP_CRIT_LOW}|<p>-</p>|`5`|
|{$TEMP_CRIT}|<p>-</p>|`75`|
|{$TEMP_WARN}|<p>-</p>|`65`|

## Template links

|Name|
|----|
|Template Module Generic SNMPv2|
|Template Module Interfaces SNMPv2|

## Discovery rules

|Name|Description|Type|Key and additional info|
|----|-----------|----|----|
|Memory Discovery|<p>-</p>|SNMP|memory.discovery|
|Temperature Discovery|<p>-</p>|SNMP|temperature.discovery|
|PSU Discovery|<p>-</p>|SNMP|psu.discovery|
|FAN Discovery|<p>-</p>|SNMP|fan.discovery|

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|CPU|CPU utilization|<p>MIB: MY-PROCESS-MIB</p><p>CPU utilization in %</p>|SNMP|system.cpu.util[myCPUUtilization5Min.0]|
|Fans|{#SNMPVALUE}: Fan status|<p>MIB: MY-SYSTEM-MIB</p>|SNMP|sensor.fan.status[mySystemFanIsNormal.{#SNMPINDEX}]|
|Inventory|Hardware model name|<p>MIB: SNMPv2-MIB</p><p>A textual description of the entity. This value should</p><p>include the full name and version identification of the system's hardware type, software operating-system, and</p><p>networking software.</p>|SNMP|system.hw.model<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Firmware version|<p>MIB: ENTITY-MIB</p>|SNMP|system.hw.firmware<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware version(revision)|<p>MIB: ENTITY-MIB</p>|SNMP|system.hw.version<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Operating system|<p>MIB: MY-SYSTEM-MIB</p>|SNMP|system.sw.os[mySystemSwVersion.0]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Memory|{#SNMPINDEX}: Memory utilization|<p>MIB: MY-MEMORY-MIB</p><p>This is the memory pool utilization currently.</p>|SNMP|vm.memory.util[myMemoryPoolCurrentUtilization.{#SNMPINDEX}]|
|Power_supply|{#SNMPVALUE}: Power supply status|<p>MIB: MY-SYSTEM-MIB</p>|SNMP|sensor.psu.status[mySystemElectricalSourceIsNormal.{#SNMPINDEX}]|
|Temperature|{#SNMPVALUE}: Temperature|<p>MIB: MY-SYSTEM-MIB</p><p>Return the current temperature of the FastSwitch.The temperature display is not supported for the current temperature returns to 0.</p>|SNMP|sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}]|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|High CPU utilization (over {$CPU.UTIL.CRIT}% for 5m)|<p>CPU utilization is too high. The system might be slow to respond.</p>|`{TEMPLATE_NAME:system.cpu.util[myCPUUtilization5Min.0].min(5m)}>{$CPU.UTIL.CRIT}`|WARNING||
|{#SNMPVALUE}: Fan is in critical state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[mySystemFanIsNormal.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS},eq)}=1`|AVERAGE||
|Firmware has changed|<p>Firmware version has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.firmware.diff()}=1 and {TEMPLATE_NAME:system.hw.firmware.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Operating system description has changed|<p>Operating system description has changed. Possible reasons that system has been updated or replaced. Ack to close.</p>|`{TEMPLATE_NAME:system.sw.os[mySystemSwVersion.0].diff()}=1 and {TEMPLATE_NAME:system.sw.os[mySystemSwVersion.0].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|{#SNMPINDEX}: High memory utilization ( >{$MEMORY.UTIL.MAX}% for 5m)|<p>The system is running out of free memory.</p>|`{TEMPLATE_NAME:vm.memory.util[myMemoryPoolCurrentUtilization.{#SNMPINDEX}].min(5m)}>{$MEMORY.UTIL.MAX}`|AVERAGE||
|{#SNMPVALUE}: Power supply is in critical state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[mySystemElectricalSourceIsNormal.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS},eq)}=1`|AVERAGE||
|{#SNMPVALUE}: Temperature is above warning threshold: >{$TEMP_WARN:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:""}-3`|WARNING|<p>**Depends on**:</p><p>- {#SNMPVALUE}: Temperature is above critical threshold: >{$TEMP_CRIT:""}</p>|
|{#SNMPVALUE}: Temperature is above critical threshold: >{$TEMP_CRIT:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:""}-3`|HIGH||
|{#SNMPVALUE}: Temperature is too low: <{$TEMP_CRIT_LOW:""}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[mySystemTemperatureCurrent.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:""}+3`|AVERAGE||

## Feedback

Please report any issues with the template at https://support.zabbix.com

