
# Template Net Extreme EXOS SNMPv2

## Overview

For Zabbix version: 4.4  

## Setup


## Zabbix configuration


### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$CPU.UTIL.CRIT}|<p>-</p>|`90`|
|{$FAN_CRIT_STATUS}|<p>-</p>|`2`|
|{$MEMORY.UTIL.MAX}|<p>-</p>|`90`|
|{$PSU_CRIT_STATUS}|<p>-</p>|`3`|
|{$TEMP_CRIT_LOW}|<p>-</p>|`5`|
|{$TEMP_CRIT_STATUS}|<p>-</p>|`1`|
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
|Memory Discovery|<p>-</p>|SNMP|memory.discovery|
|PSU Discovery|<p>Table of status of all power supplies in the system.</p>|SNMP|psu.discovery|
|FAN Discovery|<p>-</p>|SNMP|fan.discovery|

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|CPU|CPU utilization|<p>MIB: EXTREME-SOFTWARE-MONITOR-MIB</p><p>Total CPU utlization (percentage) as of last sampling.</p>|SNMP|system.cpu.util[extremeCpuMonitorTotalUtilization.0]|
|Fans|Fan {#SNMPVALUE}: Fan status|<p>MIB: EXTREME-SYSTEM-MIB</p><p>Operational status of a cooling fan.</p>|SNMP|sensor.fan.status[extremeFanOperational.{#SNMPINDEX}]|
|Fans|Fan {#SNMPVALUE}: Fan speed|<p>MIB: EXTREME-SYSTEM-MIB</p><p>The speed (RPM) of a cooling fan in the fantray {#SNMPVALUE}</p>|SNMP|sensor.fan.speed[extremeFanSpeed.{#SNMPINDEX}]|
|Inventory|Hardware model name|<p>MIB: ENTITY-MIB</p>|SNMP|system.hw.model<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware serial number|<p>MIB: ENTITY-MIB</p>|SNMP|system.hw.serialnumber<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Firmware version|<p>MIB: ENTITY-MIB</p>|SNMP|system.hw.firmware<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware version(revision)|<p>MIB: ENTITY-MIB</p>|SNMP|system.hw.version<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Operating system|<p>MIB: EXTREME-SYSTEM-MIB</p><p>The software revision of the primary image stored in this device.</p><p>This string will have a zero length if the revision is unknown, invalid or not present.</p><p>This will also be reported in RMON2 probeSoftwareRev if this is the software image currently running in the device.</p>|SNMP|system.sw.os[extremePrimarySoftwareRev.0]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Memory|#{#SNMPVALUE}: Available memory|<p>MIB: EXTREME-SOFTWARE-MONITOR-MIB</p><p>Total amount of free memory in Kbytes in the system.</p>|SNMP|vm.memory.available[extremeMemoryMonitorSystemFree.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1024`</p>|
|Memory|#{#SNMPVALUE}: Total memory|<p>MIB: EXTREME-SOFTWARE-MONITOR-MIB</p><p>Total amount of DRAM in Kbytes in the system.</p>|SNMP|vm.memory.total[extremeMemoryMonitorSystemTotal.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1024`</p>|
|Memory|#{#SNMPVALUE}: Memory utilization|<p>Memory utilization in %</p>|CALCULATED|vm.memory.util[{#SNMPVALUE}]<p>**Expression**:</p>`(last("vm.memory.total[extremeMemoryMonitorSystemTotal.{#SNMPINDEX}]") - last("vm.memory.available[extremeMemoryMonitorSystemFree.{#SNMPINDEX}]")) / last("vm.memory.total[extremeMemoryMonitorSystemTotal.{#SNMPINDEX}]") * 100`|
|Power_supply|PSU {#SNMPVALUE}: Power supply status|<p>MIB: EXTREME-SYSTEM-MIB</p><p>Status of the power supply {#SNMPVALUE}</p>|SNMP|sensor.psu.status[extremePowerSupplyStatus.{#SNMPINDEX}]|
|Temperature|Device: Temperature|<p>MIB: EXTREME-SYSTEM-MIB</p><p>Temperature readings of testpoint: Device</p><p>Reference: https://gtacknowledge.extremenetworks.com/articles/Q_A/Does-EXOS-support-temperature-polling-via-SNMP-on-all-nodes-in-a-stack</p>|SNMP|sensor.temp.value[extremeCurrentTemperature.0]|
|Temperature|Device: Temperature status|<p>MIB: EXTREME-SYSTEM-MIB</p><p>Temperature status of testpoint: Device</p>|SNMP|sensor.temp.status[extremeOverTemperatureAlarm.0]|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|High CPU utilization (over {$CPU.UTIL.CRIT}% for 5m)|<p>CPU utilization is too high. The system might be slow to respond.</p>|`{TEMPLATE_NAME:system.cpu.util[extremeCpuMonitorTotalUtilization.0].min(5m)}>{$CPU.UTIL.CRIT}`|WARNING||
|Fan {#SNMPVALUE}: Fan is in critical state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[extremeFanOperational.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS},eq)}=1`|AVERAGE||
|Device has been replaced (new serial number received)|<p>Device serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.serialnumber.diff()}=1 and {TEMPLATE_NAME:system.hw.serialnumber.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Firmware has changed|<p>Firmware version has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.firmware.diff()}=1 and {TEMPLATE_NAME:system.hw.firmware.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Operating system description has changed|<p>Operating system description has changed. Possible reasons that system has been updated or replaced. Ack to close.</p>|`{TEMPLATE_NAME:system.sw.os[extremePrimarySoftwareRev.0].diff()}=1 and {TEMPLATE_NAME:system.sw.os[extremePrimarySoftwareRev.0].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|#{#SNMPVALUE}: High memory utilization ( >{$MEMORY.UTIL.MAX}% for 5m)|<p>The system is running out of free memory.</p>|`{TEMPLATE_NAME:vm.memory.util[{#SNMPVALUE}].min(5m)}>{$MEMORY.UTIL.MAX}`|AVERAGE||
|PSU {#SNMPVALUE}: Power supply is in critical state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[extremePowerSupplyStatus.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS},eq)}=1`|AVERAGE||
|Device: Temperature is above warning threshold: >{$TEMP_WARN:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[extremeCurrentTemperature.0].avg(5m)}>{$TEMP_WARN:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[extremeCurrentTemperature.0].max(5m)}<{$TEMP_WARN:""}-3`|WARNING|<p>**Depends on**:</p><p>- Device: Temperature is above critical threshold: >{$TEMP_CRIT:""}</p>|
|Device: Temperature is above critical threshold: >{$TEMP_CRIT:""}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[extremeCurrentTemperature.0].avg(5m)}>{$TEMP_CRIT:""} or {Template Net Extreme EXOS SNMPv2:sensor.temp.status[extremeOverTemperatureAlarm.0].last(0)}={$TEMP_CRIT_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[extremeCurrentTemperature.0].max(5m)}<{$TEMP_CRIT:""}-3`|HIGH||
|Device: Temperature is too low: <{$TEMP_CRIT_LOW:""}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[extremeCurrentTemperature.0].avg(5m)}<{$TEMP_CRIT_LOW:""}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[extremeCurrentTemperature.0].min(5m)}>{$TEMP_CRIT_LOW:""}+3`|AVERAGE||

## Feedback

Please report any issues with the template at https://support.zabbix.com

