
# Template Net Netgear Fastpath SNMPv2

## Overview

For Zabbix version: 4.4  
https://kb.netgear.com/24352/MIBs-for-Smart-switches

This template was tested on:

- Netgear M5300-28G

## Setup

Refer to the vendor documentation.

## Zabbix configuration

No specific Zabbix configuration is required.

### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$CPU.UTIL.CRIT}|<p>-</p>|`90`|
|{$FAN_CRIT_STATUS:"failed"}|<p>-</p>|`2`|
|{$MEMORY.UTIL.MAX}|<p>-</p>|`90`|
|{$PSU_CRIT_STATUS:"failed"}|<p>-</p>|`2`|
|{$TEMP_CRIT_LOW}|<p>-</p>|`5`|
|{$TEMP_CRIT_STATUS}|<p>-</p>|`3`|
|{$TEMP_CRIT}|<p>-</p>|`60`|
|{$TEMP_WARN_STATUS}|<p>-</p>|`2`|
|{$TEMP_WARN}|<p>-</p>|`50`|

## Template links

|Name|
|----|
|Template Module Generic SNMPv2|
|Template Module Interfaces SNMPv2|

## Discovery rules

|Name|Description|Type|Key and additional info|
|----|-----------|----|----|
|Temperature Discovery|<p>FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorsTable</p>|SNMP|temp.discovery|
|FAN Discovery|<p>FASTPATH-BOXSERVICES-PRIVATE-MIB::1.3.6.1.4.1.4526.10.43.1.6.1.1</p>|SNMP|fan.discovery|
|PSU Discovery|<p>FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex</p>|SNMP|psu.discovery|

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|CPU|CPU utilization|<p>MIB: FASTPATH-SWITCHING-MIB</p><p>CPU utilization in %</p>|SNMP|system.cpu.util[agentSwitchCpuProcessTotalUtilization.0]<p>**Preprocessing**:</p><p>- REGEX: `60 Secs \( ([0-9\.]+)%\).+300 Secs \1`</p>|
|Fans|#{#SNMPVALUE}: Fan status|<p>MIB: FASTPATH-BOXSERVICES-PRIVATE-MIB</p><p>The status of fan</p>|SNMP|sensor.fan.status[boxServicesFanItemState.{#SNMPINDEX}]|
|Inventory|Operating system|<p>MIB: FASTPATH-SWITCHING-MIB</p><p>Operating System running on this unit</p>|SNMP|system.sw.os[agentInventoryOperatingSystem.0]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware model name|<p>MIB: FASTPATH-SWITCHING-MIB</p>|SNMP|system.hw.model<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware serial number|<p>MIB: FASTPATH-SWITCHING-MIB</p><p>Serial number of the switch</p>|SNMP|system.hw.serialnumber<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Memory|Available memory|<p>MIB: FASTPATH-SWITCHING-MIB</p><p>The total memory freed for utilization.</p>|SNMP|vm.memory.available[agentSwitchCpuProcessMemFree.0]|
|Memory|Total memory|<p>MIB: FASTPATH-SWITCHING-MIB</p><p>The total Memory allocated for the tasks</p>|SNMP|vm.memory.total[agentSwitchCpuProcessMemAvailable.0]|
|Memory|Memory utilization|<p>Memory utilization in %</p>|CALCULATED|vm.memory.util[memoryUsedPercentage.0]<p>**Expression**:</p>`(last("vm.memory.total[agentSwitchCpuProcessMemAvailable.0]")-last("vm.memory.available[agentSwitchCpuProcessMemFree.0]"))/last("vm.memory.total[agentSwitchCpuProcessMemAvailable.0]")*100`|
|Power_supply|#{#SNMPVALUE}: Power supply status|<p>MIB: FASTPATH-BOXSERVICES-PRIVATE-MIB</p><p>The status of power supply</p>|SNMP|sensor.psu.status[boxServicesPowSupplyItemState.{#SNMPINDEX}]|
|Temperature|#{#SNMPVALUE}: Temperature|<p>MIB: FASTPATH-BOXSERVICES-PRIVATE-MIB</p><p>The temperature value reported by sensor</p>|SNMP|sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}]|
|Temperature|#{#SNMPVALUE}: Temperature status|<p>MIB: FASTPATH-BOXSERVICES-PRIVATE-MIB</p><p>The state of temperature sensor</p>|SNMP|sensor.temp.status[boxServicesTempSensorState.{#SNMPINDEX}]|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|High CPU utilization (over {$CPU.UTIL.CRIT}% for 5m)|<p>CPU utilization is too high. The system might be slow to respond.</p>|`{TEMPLATE_NAME:system.cpu.util[agentSwitchCpuProcessTotalUtilization.0].min(5m)}>{$CPU.UTIL.CRIT}`|WARNING||
|#{#SNMPVALUE}: Fan is in critical state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[boxServicesFanItemState.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS:"failed"},eq)}=1`|AVERAGE||
|Operating system description has changed|<p>Operating system description has changed. Possible reasons that system has been updated or replaced. Ack to close.</p>|`{TEMPLATE_NAME:system.sw.os[agentInventoryOperatingSystem.0].diff()}=1 and {TEMPLATE_NAME:system.sw.os[agentInventoryOperatingSystem.0].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Device has been replaced (new serial number received)|<p>Device serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.serialnumber.diff()}=1 and {TEMPLATE_NAME:system.hw.serialnumber.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|High memory utilization ( >{$MEMORY.UTIL.MAX}% for 5m)|<p>The system is running out of free memory.</p>|`{TEMPLATE_NAME:vm.memory.util[memoryUsedPercentage.0].min(5m)}>{$MEMORY.UTIL.MAX}`|AVERAGE||
|#{#SNMPVALUE}: Power supply is in critical state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[boxServicesPowSupplyItemState.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS:"failed"},eq)}=1`|AVERAGE||
|#{#SNMPVALUE}: Temperature is above warning threshold: >{$TEMP_WARN:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:""} or {Template Net Netgear Fastpath SNMPv2:sensor.temp.status[boxServicesTempSensorState.{#SNMPINDEX}].last(0)}={$TEMP_WARN_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:""}-3`|WARNING|<p>**Depends on**:</p><p>- #{#SNMPVALUE}: Temperature is above critical threshold: >{$TEMP_CRIT:""}</p>|
|#{#SNMPVALUE}: Temperature is above critical threshold: >{$TEMP_CRIT:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:""} or {Template Net Netgear Fastpath SNMPv2:sensor.temp.status[boxServicesTempSensorState.{#SNMPINDEX}].last(0)}={$TEMP_CRIT_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:""}-3`|HIGH||
|#{#SNMPVALUE}: Temperature is too low: <{$TEMP_CRIT_LOW:""}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[boxServicesTempSensorTemperature.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:""}+3`|AVERAGE||

## Feedback

Please report any issues with the template at https://support.zabbix.com

