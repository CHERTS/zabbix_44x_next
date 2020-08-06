
# Template Server HP iLO SNMPv2

## Overview

For Zabbix version: 4.4  
for HP iLO adapters that support SNMP get. Or via operating system, using SNMP HP subagent

This template was tested on:

- iLo4, HP Proliant G9

## Setup

Refer to the vendor documentation.

## Zabbix configuration

No specific Zabbix configuration is required.

### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$DISK_ARRAY_CACHE_BATTERY_CRIT_STATUS:"capacitorFailed"}|<p>-</p>|`7`|
|{$DISK_ARRAY_CACHE_BATTERY_CRIT_STATUS:"failed"}|<p>-</p>|`4`|
|{$DISK_ARRAY_CACHE_BATTERY_WARN_STATUS:"degraded"}|<p>-</p>|`5`|
|{$DISK_ARRAY_CACHE_BATTERY_WARN_STATUS:"notPresent"}|<p>-</p>|`6`|
|{$DISK_ARRAY_CACHE_CRIT_STATUS:"cacheModCriticalFailure"}|<p>-</p>|`8`|
|{$DISK_ARRAY_CACHE_OK_STATUS:"enabled"}|<p>-</p>|`3`|
|{$DISK_ARRAY_CACHE_WARN_STATUS:"cacheModDegradedFailsafeSpeed"}|<p>-</p>|`7`|
|{$DISK_ARRAY_CACHE_WARN_STATUS:"cacheModFlashMemNotAttached"}|<p>-</p>|`6`|
|{$DISK_ARRAY_CACHE_WARN_STATUS:"cacheReadCacheNotMapped"}|<p>-</p>|`9`|
|{$DISK_ARRAY_CACHE_WARN_STATUS:"invalid"}|<p>-</p>|`2`|
|{$DISK_ARRAY_CRIT_STATUS}|<p>-</p>|`4`|
|{$DISK_ARRAY_WARN_STATUS}|<p>-</p>|`3`|
|{$DISK_FAIL_STATUS}|<p>-</p>|`3`|
|{$DISK_SMART_FAIL_STATUS:"replaceDrive"}|<p>-</p>|`3`|
|{$DISK_SMART_FAIL_STATUS:"replaceDriveSSDWearOut"}|<p>-</p>|`4`|
|{$DISK_WARN_STATUS}|<p>-</p>|`4`|
|{$FAN_CRIT_STATUS}|<p>-</p>|`4`|
|{$FAN_WARN_STATUS}|<p>-</p>|`3`|
|{$HEALTH_CRIT_STATUS}|<p>-</p>|`4`|
|{$HEALTH_WARN_STATUS}|<p>-</p>|`3`|
|{$PSU_CRIT_STATUS}|<p>-</p>|`4`|
|{$PSU_WARN_STATUS}|<p>-</p>|`3`|
|{$TEMP_CRIT:"Ambient"}|<p>-</p>|`35`|
|{$TEMP_CRIT_LOW}|<p>-</p>|`5`|
|{$TEMP_CRIT}|<p>-</p>|`60`|
|{$TEMP_WARN:"Ambient"}|<p>-</p>|`30`|
|{$TEMP_WARN}|<p>-</p>|`50`|
|{$VDISK_CRIT_STATUS}|<p>-</p>|`3`|
|{$VDISK_OK_STATUS}|<p>-</p>|`2`|

## Template links

|Name|
|----|
|Template Module Generic SNMPv2|

## Discovery rules

|Name|Description|Type|Key and additional info|
|----|-----------|----|----|
|Temperature Discovery|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable</p>|SNMP|tempDescr.discovery<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `(4|8|9|12|13)`</p>|
|Temperature Discovery Ambient|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable with ambient(11) and 0.1 index filter</p>|SNMP|tempDescr.discovery.ambient<p>**Filter**:</p>AND_OR <p>- B: {#SNMPINDEX} MATCHES_REGEX `0\.1`</p><p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `11`</p>|
|Temperature Discovery CPU|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable with cpu(6) filter</p>|SNMP|tempDescr.discovery.cpu<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `6`</p>|
|Temperature Discovery Memory|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable with memory(7) filter</p>|SNMP|tempDescr.discovery.memory<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `7`</p>|
|Temperature Discovery PSU|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable with powerSupply(10) filter</p>|SNMP|tempDescr.discovery.psu<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `10`</p>|
|Temperature Discovery I/O|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable with ioBoard(5) filter</p>|SNMP|tempDescr.discovery.io<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `5`</p>|
|Temperature Discovery System|<p>Scanning table of Temperature Sensor Entries: CPQHLTH-MIB::cpqHeTemperatureTable with system(3) filter</p>|SNMP|tempDescr.discovery.io<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `3`</p>|
|PSU Discovery|<p>CPQHLTH-MIB::cpqHeFltTolPowerSupplyStatus</p>|SNMP|psu.discovery|
|FAN Discovery|<p>CPQHLTH-MIB::cpqHeFltTolFanCondition</p>|SNMP|fan.discovery|
|Array Controller Discovery|<p>Scanning table of Array controllers: CPQIDA-MIB::cpqDaCntlrTable</p>|SNMP|array.discovery|
|Array Controller Cache Discovery|<p>Scanning table of Array controllers: CPQIDA-MIB::cpqDaAccelTable</p>|SNMP|array.cache.discovery|
|Physical Disk Discovery|<p>Scanning  table of physical drive entries CPQIDA-MIB::cpqDaPhyDrvTable.</p>|SNMP|physicalDisk.discovery|
|Virtual Disk Discovery|<p>CPQIDA-MIB::cpqDaLogDrvTable</p>|SNMP|virtualdisk.discovery|

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|Disk_arrays|{#CNTLR_LOCATION}: Disk array controller status|<p>MIB: CPQIDA-MIB</p><p>This value represents the overall condition of this controller,</p><p>and any associated logical drives,physical drives, and array accelerators.</p>|SNMP|system.hw.diskarray.status[cpqDaCntlrCondition.{#SNMPINDEX}]|
|Disk_arrays|{#CNTLR_LOCATION}: Disk array controller model|<p>MIB: CPQIDA-MIB</p><p>Array Controller Model. The type of controller card.</p>|SNMP|system.hw.diskarray.model[cpqDaCntlrModel.{#SNMPINDEX}]|
|Disk_arrays|#{#CACHE_CNTRL_INDEX}: Disk array cache controller status|<p>MIB: CPQIDA-MIB</p><p>Cache Module/Operations Status. This describes the status of the cache module and/or cache operations.</p><p>Note that for some controller models, a cache module board that physically attaches to the controller or chipset may not be an available option.</p>|SNMP|system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}]|
|Disk_arrays|#{#CACHE_CNTRL_INDEX}: Disk array cache controller battery status|<p>MIB: CPQIDA-MIB</p><p>Cache Module Board Backup Power Status. This monitors the status of each backup power source on the board.</p><p>The backup power source can only recharge when the system has power applied. The type of backup power source used is indicated by cpqDaAccelBackupPowerSource.</p><p>The following values are valid:</p><p>Other (1)  Indicates that the instrument agent does not recognize  backup power status.  You may need to update your software.</p><p>Ok (2)  The backup power source is fully charged.</p><p>Recharging (3)  The array controller has one or more cache module backup power  sources that are recharging.</p><p>Cache module operations such as Battery/Flash Backed Write Cache, Expansion, Extension and Migration are temporarily suspended until the backup power source is fully charged.</p><p>Cache module operations will automatically resume  when charging is complete.</p><p>Failed (4)  The battery pack is below the sufficient voltage level and  has not recharged in 36 hours.</p><p>Your Cache Module board  needs to be serviced.</p><p>Degraded (5)  The battery is still operating, however, one of the batteries  in the pack has failed to recharge properly.</p><p>Your Cache  Module board should be serviced as soon as possible.</p><p>NotPresent (6)  A backup power source is not present on the cache module board. Some controllers do not have backup power sources.</p><p>Capacitor Failed (7)  The flash backed cache module capacitor is below the sufficient voltage level and has not recharged in 10 minutes.  Your Cache Module board needs to be serviced.</p><p>                </p>|SNMP|system.hw.diskarray.cache.battery.status[cpqDaAccelBattery.{#SNMPINDEX}]|
|Fans|Fan {#SNMPINDEX}: Fan status|<p>MIB: CPQHLTH-MIB</p><p>The condition of the fan.</p><p>This value will be one of the following:</p><p>other(1)  Fan status detection is not supported by this system or driver.</p><p>ok(2)  The fan is operating properly.</p><p>degraded(2)  A redundant fan is not operating properly.</p><p>failed(4)  A non-redundant fan is not operating properly.</p>|SNMP|sensor.fan.status[cpqHeFltTolFanCondition.{#SNMPINDEX}]|
|Inventory|Hardware model name|<p>MIB: CPQSINFO-MIB</p><p>The machine product name.The name of the machine used in this system.</p>|SNMP|system.hw.model<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware serial number|<p>MIB: CPQSINFO-MIB</p><p>The serial number of the physical system unit. The string will be empty if the system does not report the serial number function.</p>|SNMP|system.hw.serialnumber<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Physical_disks|{#DISK_LOCATION}: Physical disk status|<p>MIB: CPQIDA-MIB</p><p>Physical Drive Status. This shows the status of the physical drive. The following values are valid for the physical drive status:</p><p>other (1)  Indicates that the instrument agent does not recognize  the drive.</p><p>You may need to upgrade your instrument agent  and/or driver software.</p><p>ok (2)  Indicates the drive is functioning properly.</p><p>failed (3)  Indicates that the drive is no longer operating and  should be replaced.</p><p>predictiveFailure(4)  Indicates that the drive has a predictive failure error and  should be replaced.</p>|SNMP|system.hw.physicaldisk.status[cpqDaPhyDrvStatus.{#SNMPINDEX}]|
|Physical_disks|{#DISK_LOCATION}: Physical disk S.M.A.R.T. status|<p>MIB: CPQIDA-MIB</p><p>Physical Drive S.M.A.R.T Status.The following values are defined:</p><p>other(1)  The agent is unable to determine if the status of S.M.A.R.T  predictive failure monitoring for this drive.</p><p>ok(2)  Indicates the drive is functioning properly.</p><p>replaceDrive(3)  Indicates that the drive has a S.M.A.R.T predictive failure  error and should be replaced.</p>|SNMP|system.hw.physicaldisk.smart_status[cpqDaPhyDrvSmartStatus.{#SNMPINDEX}]|
|Physical_disks|{#DISK_LOCATION}: Physical disk serial number|<p>MIB: CPQIDA-MIB</p><p>Physical Drive Serial Number.</p><p>This is the serial number assigned to the physical drive.</p><p>This value is based upon the serial number as returned by the SCSI inquiry command</p><p>but may have been modified due to space limitations.  This can be used for identification purposes.</p>|SNMP|system.hw.physicaldisk.serialnumber[cpqDaPhyDrvSerialNum.{#SNMPINDEX}]|
|Physical_disks|{#DISK_LOCATION}: Physical disk model name|<p>MIB: CPQIDA-MIB</p><p>Physical Drive Model.This is a text description of the physical drive.</p><p>The text that appears depends upon who manufactured the drive and the drive type.</p><p>If a drive fails, note the model to identify the type of drive necessary for replacement.</p><p>If a model number is not present, you may not have properly initialized the drive array to which the physical drive is attached for monitoring.</p>|SNMP|system.hw.physicaldisk.model[cpqDaPhyDrvModel.{#SNMPINDEX}]|
|Physical_disks|{#DISK_LOCATION}: Physical disk media type|<p>MIB: CPQIDA-MIB</p><p>Drive Array Physical Drive Media Type.The following values are defined:</p><p>other(1)  The instrument agent is unable to determine the physical drive’s media type.</p><p>rotatingPlatters(2)  The physical drive media is composed of rotating platters.</p><p>solidState(3)  The physical drive media is composed of solid state electronics.</p>|SNMP|system.hw.physicaldisk.media_type[cpqDaPhyDrvMediaType.{#SNMPINDEX}]|
|Physical_disks|{#DISK_LOCATION}: Disk size|<p>MIB: CPQIDA-MIB</p><p>Physical Drive Size in MB.</p><p>This is the size of the physical drive in megabytes.</p><p>This value is calculated using the value 1,048,576 (2^20) as a megabyte.</p><p>Drive manufacturers sometimes use the number 1,000,000 as a megabyte when giving drive capacities so this value may differ</p><p>from the advertised size of a drive. This field is only applicable for controllers which support SCSI drives,</p><p>and therefore is not supported by the IDA or IDA-2 controllers. The field will contain 0xFFFFFFFF if the drive capacity cannot be calculated</p><p>or if the controller does not support SCSI drives.</p>|SNMP|system.hw.physicaldisk.size[cpqDaPhyDrvMediaType.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1048576`</p>|
|Power_supply|Chassis {#CHASSIS_NUM}, bay {#BAY_NUM}: Power supply status|<p>MIB: CPQHLTH-MIB</p><p>The condition of the power supply. This value will be one of the following:</p><p>other(1)  The status could not be determined or not present.</p><p>ok(2)  The power supply is operating normally.</p><p>degraded(3)  A temperature sensor, fan or other power supply component is  outside of normal operating range.</p><p>failed(4)  A power supply component detects a condition that could  permanently damage the system.</p>|SNMP|sensor.psu.status[cpqHeFltTolPowerSupplyCondition.{#SNMPINDEX}]|
|Status|Overall system health status|<p>MIB: CPQHLTH-MIB</p><p>The overall condition. This object represents the overall status of the server information represented by this MIB.</p>|SNMP|system.status[cpqHeMibCondition.0]|
|Temperature|System: Temperature status|<p>MIB: CPQHLTH-MIB</p><p>This value specifies the overall condition of the system's thermal environment.</p><p>This value will be one of the following:</p><p>other(1)  Temperature could not be determined.</p><p>ok(2)  The temperature sensor is within normal operating range.</p><p>degraded(3)  The temperature sensor is outside of normal operating range.</p><p>failed(4)  The temperature sensor detects a condition that could  permanently damage the system.</p>|SNMP|sensor.temp.status[cpqHeThermalCondition.0]|
|Temperature|{#SNMPINDEX}: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: {#SNMPINDEX}</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}]|
|Temperature|{#SNMPINDEX}: Temperature sensor location|<p>MIB: CPQHLTH-MIB</p><p>This specifies the location of the temperature sensor present in the system.</p>|SNMP|sensor.temp.locale[cpqHeTemperatureLocale.{#SNMPINDEX}]|
|Temperature|Ambient: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: Ambient</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}]|
|Temperature|CPU-{#SNMPINDEX}: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: CPU-{#SNMPINDEX}</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}]|
|Temperature|Memory-{#SNMPINDEX}: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: Memory-{#SNMPINDEX}</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}]|
|Temperature|PSU-{#SNMPINDEX}: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: PSU-{#SNMPINDEX}</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}]|
|Temperature|I/O-{#SNMPINDEX}: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: I/O-{#SNMPINDEX}</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"]|
|Temperature|System-{#SNMPINDEX}: Temperature|<p>MIB: CPQHLTH-MIB</p><p>Temperature readings of testpoint: System-{#SNMPINDEX}</p>|SNMP|sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPINDEX}({#DISK_NAME}): Status|<p>Logical Drive Status.</p>|SNMP|system.hw.virtualdisk.status[cpqDaLogDrvStatus.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPINDEX}({#DISK_NAME}): Layout type |<p>Logical Drive Fault Tolerance.</p><p>This shows the fault tolerance mode of the logical drive.</p>|SNMP|system.hw.virtualdisk.layout[cpqDaLogDrvFaultTol.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPINDEX}({#DISK_NAME}): Disk size|<p>Logical Drive Size.</p><p>This is the size of the logical drive in megabytes.  This value</p><p>is calculated using the value 1,048,576 (2^20) as a megabyte.</p><p>Drive manufacturers sometimes use the number 1,000,000 as a</p><p>megabyte when giving drive capacities so this value may</p><p>differ from the advertised size of a drive.</p>|SNMP|system.hw.virtualdisk.size[cpqDaLogDrvSize.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1048576`</p>|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|{#CNTLR_LOCATION}: Disk array controller is in critical state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.status[cpqDaCntlrCondition.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CRIT_STATUS},eq)}=1`|HIGH||
|{#CNTLR_LOCATION}: Disk array controller is in warning state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.status[cpqDaCntlrCondition.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_WARN_STATUS},eq)}=1`|AVERAGE|<p>**Depends on**:</p><p>- {#CNTLR_LOCATION}: Disk array controller is in critical state</p>|
|#{#CACHE_CNTRL_INDEX}: Disk array cache controller is in critical state!|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_CRIT_STATUS:"cacheModCriticalFailure"},eq)}=1`|AVERAGE||
|#{#CACHE_CNTRL_INDEX}: Disk array cache controller is in warning state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_WARN_STATUS:"invalid"},eq)}=1 or {TEMPLATE_NAME:system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_WARN_STATUS:"cacheModDegradedFailsafeSpeed"},eq)}=1 or {TEMPLATE_NAME:system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_WARN_STATUS:"cacheReadCacheNotMapped"},eq)}=1 or {TEMPLATE_NAME:system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_WARN_STATUS:"cacheModFlashMemNotAttached"},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- #{#CACHE_CNTRL_INDEX}: Disk array cache controller is in critical state!</p>|
|#{#CACHE_CNTRL_INDEX}: Disk array cache controller is not in optimal state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.status[cpqDaAccelStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_OK_STATUS:"enabled"},ne)}=1`|WARNING|<p>**Depends on**:</p><p>- #{#CACHE_CNTRL_INDEX}: Disk array cache controller is in critical state!</p><p>- #{#CACHE_CNTRL_INDEX}: Disk array cache controller is in warning state</p>|
|#{#CACHE_CNTRL_INDEX}: Disk array cache controller battery is in critical state!|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[cpqDaAccelBattery.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_CRIT_STATUS:"failed"},eq)}=1 or {TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[cpqDaAccelBattery.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_CRIT_STATUS:"capacitorFailed"},eq)}=1`|AVERAGE||
|#{#CACHE_CNTRL_INDEX}: Disk array cache controller battery is in warning state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[cpqDaAccelBattery.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_WARN_STATUS:"degraded"},eq)}=1 or {TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[cpqDaAccelBattery.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_WARN_STATUS:"notPresent"},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- #{#CACHE_CNTRL_INDEX}: Disk array cache controller battery is in critical state!</p>|
|Fan {#SNMPINDEX}: Fan is in critical state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[cpqHeFltTolFanCondition.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS},eq)}=1`|AVERAGE||
|Fan {#SNMPINDEX}: Fan is in warning state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[cpqHeFltTolFanCondition.{#SNMPINDEX}].count(#1,{$FAN_WARN_STATUS},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- Fan {#SNMPINDEX}: Fan is in critical state</p>|
|Device has been replaced (new serial number received)|<p>Device serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.serialnumber.diff()}=1 and {TEMPLATE_NAME:system.hw.serialnumber.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|{#DISK_LOCATION}: Physical disk failed|<p>Please check physical disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.status[cpqDaPhyDrvStatus.{#SNMPINDEX}].count(#1,{$DISK_FAIL_STATUS},eq)}=1`|HIGH||
|{#DISK_LOCATION}: Physical disk is in warning state|<p>Please check physical disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.status[cpqDaPhyDrvStatus.{#SNMPINDEX}].count(#1,{$DISK_WARN_STATUS},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- {#DISK_LOCATION}: Physical disk failed</p>|
|{#DISK_LOCATION}: Physical disk S.M.A.R.T. failed|<p>Disk probably requires replacement.</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.smart_status[cpqDaPhyDrvSmartStatus.{#SNMPINDEX}].count(#1,{$DISK_SMART_FAIL_STATUS:"replaceDrive"},eq)}=1 or {TEMPLATE_NAME:system.hw.physicaldisk.smart_status[cpqDaPhyDrvSmartStatus.{#SNMPINDEX}].count(#1,{$DISK_SMART_FAIL_STATUS:"replaceDriveSSDWearOut"},eq)}=1`|HIGH|<p>**Depends on**:</p><p>- {#DISK_LOCATION}: Physical disk failed</p>|
|{#DISK_LOCATION}: Disk has been replaced (new serial number received)|<p>Disk serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.serialnumber[cpqDaPhyDrvSerialNum.{#SNMPINDEX}].diff()}=1 and {TEMPLATE_NAME:system.hw.physicaldisk.serialnumber[cpqDaPhyDrvSerialNum.{#SNMPINDEX}].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Chassis {#CHASSIS_NUM}, bay {#BAY_NUM}: Power supply is in critical state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[cpqHeFltTolPowerSupplyCondition.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS},eq)}=1`|AVERAGE||
|Chassis {#CHASSIS_NUM}, bay {#BAY_NUM}: Power supply is in warning state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[cpqHeFltTolPowerSupplyCondition.{#SNMPINDEX}].count(#1,{$PSU_WARN_STATUS},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- Chassis {#CHASSIS_NUM}, bay {#BAY_NUM}: Power supply is in critical state</p>|
|System status is in critical state|<p>Please check the device for errors</p>|`{TEMPLATE_NAME:system.status[cpqHeMibCondition.0].count(#1,{$HEALTH_CRIT_STATUS},eq)}=1`|HIGH||
|System status is in warning state|<p>Please check the device for warnings</p>|`{TEMPLATE_NAME:system.status[cpqHeMibCondition.0].count(#1,{$HEALTH_WARN_STATUS},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- System status is in critical state</p>|
|{#SNMPINDEX}: Temperature is above warning threshold: >{$TEMP_WARN:"{#SNMPINDEX}"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"{#SNMPINDEX}"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"{#SNMPINDEX}"}-3`|WARNING|<p>**Depends on**:</p><p>- {#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"{#SNMPINDEX}"}</p>|
|{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"{#SNMPINDEX}"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"{#SNMPINDEX}"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"{#SNMPINDEX}"}-3`|HIGH||
|{#SNMPINDEX}: Temperature is too low: <{$TEMP_CRIT_LOW:"{#SNMPINDEX}"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"{#SNMPINDEX}"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"{#SNMPINDEX}"}+3`|AVERAGE||
|Ambient: Temperature is above warning threshold: >{$TEMP_WARN:"Ambient"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"Ambient"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"Ambient"}-3`|WARNING|<p>**Depends on**:</p><p>- Ambient: Temperature is above critical threshold: >{$TEMP_CRIT:"Ambient"}</p>|
|Ambient: Temperature is above critical threshold: >{$TEMP_CRIT:"Ambient"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"Ambient"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"Ambient"}-3`|HIGH||
|Ambient: Temperature is too low: <{$TEMP_CRIT_LOW:"Ambient"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"Ambient"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Ambient.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"Ambient"}+3`|AVERAGE||
|CPU-{#SNMPINDEX}: Temperature is above warning threshold: >{$TEMP_WARN:"CPU"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"CPU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"CPU"}-3`|WARNING|<p>**Depends on**:</p><p>- CPU-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"CPU"}</p>|
|CPU-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"CPU"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"CPU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"CPU"}-3`|HIGH||
|CPU-{#SNMPINDEX}: Temperature is too low: <{$TEMP_CRIT_LOW:"CPU"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"CPU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.CPU.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"CPU"}+3`|AVERAGE||
|Memory-{#SNMPINDEX}: Temperature is above warning threshold: >{$TEMP_WARN:"Memory"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"Memory"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"Memory"}-3`|WARNING|<p>**Depends on**:</p><p>- Memory-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"Memory"}</p>|
|Memory-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"Memory"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"Memory"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"Memory"}-3`|HIGH||
|Memory-{#SNMPINDEX}: Temperature is too low: <{$TEMP_CRIT_LOW:"Memory"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"Memory"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.Memory.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"Memory"}+3`|AVERAGE||
|PSU-{#SNMPINDEX}: Temperature is above warning threshold: >{$TEMP_WARN:"PSU"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"PSU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"PSU"}-3`|WARNING|<p>**Depends on**:</p><p>- PSU-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"PSU"}</p>|
|PSU-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"PSU"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"PSU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"PSU"}-3`|HIGH||
|PSU-{#SNMPINDEX}: Temperature is too low: <{$TEMP_CRIT_LOW:"PSU"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"PSU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.PSU.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"PSU"}+3`|AVERAGE||
|I/O-{#SNMPINDEX}: Temperature is above warning threshold: >{$TEMP_WARN:"I/O"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"].avg(5m)}>{$TEMP_WARN:"I/O"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"].max(5m)}<{$TEMP_WARN:"I/O"}-3`|WARNING|<p>**Depends on**:</p><p>- I/O-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"I/O"}</p>|
|I/O-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"I/O"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"].avg(5m)}>{$TEMP_CRIT:"I/O"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"].max(5m)}<{$TEMP_CRIT:"I/O"}-3`|HIGH||
|I/O-{#SNMPINDEX}: Temperature is too low: <{$TEMP_CRIT_LOW:"I/O"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"].avg(5m)}<{$TEMP_CRIT_LOW:"I/O"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius."I/O.{#SNMPINDEX}"].min(5m)}>{$TEMP_CRIT_LOW:"I/O"}+3`|AVERAGE||
|System-{#SNMPINDEX}: Temperature is above warning threshold: >{$TEMP_WARN:"Device"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"Device"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"Device"}-3`|WARNING|<p>**Depends on**:</p><p>- System-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"Device"}</p>|
|System-{#SNMPINDEX}: Temperature is above critical threshold: >{$TEMP_CRIT:"Device"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"Device"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"Device"}-3`|HIGH||
|System-{#SNMPINDEX}: Temperature is too low: <{$TEMP_CRIT_LOW:"Device"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"Device"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[cpqHeTemperatureCelsius.System.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"Device"}+3`|AVERAGE||
|Disk {#SNMPINDEX}({#DISK_NAME}): Virtual disk failed|<p>Please check virtual disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.virtualdisk.status[cpqDaLogDrvStatus.{#SNMPINDEX}].count(#1,{$VDISK_CRIT_STATUS},eq)}=1`|HIGH||
|Disk {#SNMPINDEX}({#DISK_NAME}): Virtual disk is not in OK state|<p>Please check virtual disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.virtualdisk.status[cpqDaLogDrvStatus.{#SNMPINDEX}].count(#1,{$VDISK_OK_STATUS},ne)}=1`|WARNING|<p>**Depends on**:</p><p>- Disk {#SNMPINDEX}({#DISK_NAME}): Virtual disk failed</p>|

## Feedback

Please report any issues with the template at https://support.zabbix.com

