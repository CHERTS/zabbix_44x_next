
# Template Net Dell Force S-Series SNMPv2

## Overview

For Zabbix version: 4.4  

## Setup


## Zabbix configuration


### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$CPU.UTIL.CRIT}|<p>-</p>|`90`|
|{$FAN_CRIT_STATUS}|<p>-</p>|`2`|
|{$FAN_OK_STATUS}|<p>-</p>|`1`|
|{$MEMORY.UTIL.MAX}|<p>-</p>|`90`|
|{$PSU_CRIT_STATUS}|<p>-</p>|`2`|
|{$PSU_OK_STATUS}|<p>-</p>|`1`|
|{$TEMP_CRIT_LOW}|<p>-</p>|`5`|
|{$TEMP_CRIT}|<p>-</p>|`65`|
|{$TEMP_WARN}|<p>-</p>|`55`|

## Template links

|Name|
|----|
|Template Module EtherLike-MIB SNMPv2|
|Template Module Generic SNMPv2|
|Template Module Interfaces SNMPv2|

## Discovery rules

|Name|Description|Type|Key and additional info|
|----|-----------|----|----|
|CPU and Memory and Flash Discovery|<p>-</p>|SNMP|module.discovery|
|PSU Discovery|<p>A list of power supply residents in the S-series chassis.</p>|SNMP|psu.discovery|
|FAN Discovery|<p>-</p>|SNMP|fan.discovery|
|Stack Unit Discovery|<p>-</p>|SNMP|stack.discovery|

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|CPU|#{#SNMPINDEX}: CPU utilization|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>CPU utilization in percentage for last 1 minute.</p>|SNMP|system.cpu.util[chStackUnitCpuUtil1Min.{#SNMPINDEX}]|
|Fans|Fan {#SNMPVALUE}: Fan status|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>The status of the fan tray {#SNMPVALUE}.</p>|SNMP|sensor.fan.status[chSysFanTrayOperStatus.{#SNMPINDEX}]|
|Inventory|#{#SNMPVALUE}: Hardware model name|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>The plugged-in model ID for this unit.</p>|SNMP|system.hw.model[chStackUnitModelID.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|#{#SNMPVALUE}: Hardware serial number|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>The unit's serial number.</p>|SNMP|system.hw.serialnumber[chStackUnitSerialNumber.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|#{#SNMPVALUE}: Hardware version(revision)|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>The unit manufacturer's product revision</p>|SNMP|system.hw.version[chStackUnitProductRev.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|#{#SNMPVALUE}: Operating system|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>Current code version of this unit.</p>|SNMP|system.sw.os[chStackUnitCodeVersion.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Memory|#{#SNMPINDEX}: Memory utilization|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>Total memory usage in percentage.</p>|SNMP|vm.memory.util[chStackUnitMemUsageUtil.{#SNMPINDEX}]|
|Power_supply|PSU {#SNMPVALUE}: Power supply status|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>The status of the power supply {#SNMPVALUE}</p>|SNMP|sensor.psu.status[chSysPowerSupplyOperStatus.{#SNMPINDEX}]|
|Temperature|Device {#SNMPVALUE}: Temperature|<p>MIB: F10-S-SERIES-CHASSIS-MIB</p><p>The temperature of the unit.</p>|SNMP|sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}]|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|#{#SNMPINDEX}: High CPU utilization (over {$CPU.UTIL.CRIT}% for 5m)|<p>CPU utilization is too high. The system might be slow to respond.</p>|`{TEMPLATE_NAME:system.cpu.util[chStackUnitCpuUtil1Min.{#SNMPINDEX}].min(5m)}>{$CPU.UTIL.CRIT}`|WARNING||
|Fan {#SNMPVALUE}: Fan is in critical state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[chSysFanTrayOperStatus.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS},eq)}=1`|AVERAGE||
|Fan {#SNMPVALUE}: Fan is not in normal state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[chSysFanTrayOperStatus.{#SNMPINDEX}].count(#1,{$FAN_OK_STATUS},ne)}=1`|INFO|<p>**Depends on**:</p><p>- Fan {#SNMPVALUE}: Fan is in critical state</p>|
|#{#SNMPVALUE}: Device has been replaced (new serial number received)|<p>Device serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.serialnumber[chStackUnitSerialNumber.{#SNMPINDEX}].diff()}=1 and {TEMPLATE_NAME:system.hw.serialnumber[chStackUnitSerialNumber.{#SNMPINDEX}].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|#{#SNMPVALUE}: Operating system description has changed|<p>Operating system description has changed. Possible reasons that system has been updated or replaced. Ack to close.</p>|`{TEMPLATE_NAME:system.sw.os[chStackUnitCodeVersion.{#SNMPINDEX}].diff()}=1 and {TEMPLATE_NAME:system.sw.os[chStackUnitCodeVersion.{#SNMPINDEX}].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|#{#SNMPINDEX}: High memory utilization ( >{$MEMORY.UTIL.MAX}% for 5m)|<p>The system is running out of free memory.</p>|`{TEMPLATE_NAME:vm.memory.util[chStackUnitMemUsageUtil.{#SNMPINDEX}].min(5m)}>{$MEMORY.UTIL.MAX}`|AVERAGE||
|PSU {#SNMPVALUE}: Power supply is in critical state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[chSysPowerSupplyOperStatus.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS},eq)}=1`|AVERAGE||
|PSU {#SNMPVALUE}: Power supply is not in normal state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[chSysPowerSupplyOperStatus.{#SNMPINDEX}].count(#1,{$PSU_OK_STATUS},ne)}=1`|INFO|<p>**Depends on**:</p><p>- PSU {#SNMPVALUE}: Power supply is in critical state</p>|
|Device {#SNMPVALUE}: Temperature is above warning threshold: >{$TEMP_WARN:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:""}-3`|WARNING|<p>**Depends on**:</p><p>- Device {#SNMPVALUE}: Temperature is above critical threshold: >{$TEMP_CRIT:""}</p>|
|Device {#SNMPVALUE}: Temperature is above critical threshold: >{$TEMP_CRIT:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:""}-3`|HIGH||
|Device {#SNMPVALUE}: Temperature is too low: <{$TEMP_CRIT_LOW:""}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[chStackUnitTemp.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:""}+3`|AVERAGE||

## Feedback

Please report any issues with the template at https://support.zabbix.com

