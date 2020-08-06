
# Template Server Dell iDRAC SNMPv2

## Overview

For Zabbix version: 4.4  
for Dell servers with iDRAC controllers
http://www.dell.com/support/manuals/us/en/19/dell-openmanage-server-administrator-v8.3/snmp_idrac8/idrac-mib?guid=guid-e686536d-bc8e-4e09-8e8b-de8eb052efee
Supported systems: http://www.dell.com/support/manuals/us/en/04/dell-openmanage-server-administrator-v8.3/snmp_idrac8/supported-systems?guid=guid-f72b75ba-e686-4e8a-b8c5-ca11c7c21381

This template was tested on:

- iDRAC7, PowerEdge R620
- iDRAC8, PowerEdge R730xd
- iDRAC8, PowerEdge R720

## Setup

Refer to the vendor documentation.

## Zabbix configuration

No specific Zabbix configuration is required.

### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$DISK_ARRAY_CACHE_BATTERY_CRIT_STATUS}|<p>-</p>|`3`|
|{$DISK_ARRAY_CACHE_BATTERY_OK_STATUS}|<p>-</p>|`2`|
|{$DISK_ARRAY_CACHE_BATTERY_WARN_STATUS}|<p>-</p>|`4`|
|{$DISK_ARRAY_CRIT_STATUS:"critical"}|<p>-</p>|`5`|
|{$DISK_ARRAY_FAIL_STATUS:"nonRecoverable"}|<p>-</p>|`6`|
|{$DISK_ARRAY_WARN_STATUS:"nonCritical"}|<p>-</p>|`4`|
|{$DISK_FAIL_STATUS:"critical"}|<p>-</p>|`5`|
|{$DISK_FAIL_STATUS:"nonRecoverable"}|<p>-</p>|`6`|
|{$DISK_SMART_FAIL_STATUS}|<p>-</p>|`1`|
|{$DISK_WARN_STATUS:"nonCritical"}|<p>-</p>|`4`|
|{$FAN_CRIT_STATUS:"criticalLower"}|<p>-</p>|`8`|
|{$FAN_CRIT_STATUS:"criticalUpper"}|<p>-</p>|`5`|
|{$FAN_CRIT_STATUS:"failed"}|<p>-</p>|`10`|
|{$FAN_CRIT_STATUS:"nonRecoverableLower"}|<p>-</p>|`9`|
|{$FAN_CRIT_STATUS:"nonRecoverableUpper"}|<p>-</p>|`6`|
|{$FAN_WARN_STATUS:"nonCriticalLower"}|<p>-</p>|`7`|
|{$FAN_WARN_STATUS:"nonCriticalUpper"}|<p>-</p>|`4`|
|{$HEALTH_CRIT_STATUS}|<p>-</p>|`5`|
|{$HEALTH_DISASTER_STATUS}|<p>-</p>|`6`|
|{$HEALTH_WARN_STATUS}|<p>-</p>|`4`|
|{$PSU_CRIT_STATUS:"critical"}|<p>-</p>|`5`|
|{$PSU_CRIT_STATUS:"nonRecoverable"}|<p>-</p>|`6`|
|{$PSU_WARN_STATUS:"nonCritical"}|<p>-</p>|`4`|
|{$TEMP_CRIT:"Ambient"}|<p>-</p>|`35`|
|{$TEMP_CRIT:"CPU"}|<p>-</p>|`75`|
|{$TEMP_CRIT_LOW}|<p>-</p>|`5`|
|{$TEMP_CRIT_STATUS}|<p>-</p>|`5`|
|{$TEMP_CRIT}|<p>-</p>|`60`|
|{$TEMP_DISASTER_STATUS}|<p>-</p>|`6`|
|{$TEMP_WARN:"Ambient"}|<p>-</p>|`30`|
|{$TEMP_WARN:"CPU"}|<p>-</p>|`70`|
|{$TEMP_WARN_STATUS}|<p>-</p>|`4`|
|{$TEMP_WARN}|<p>-</p>|`50`|
|{$VDISK_CRIT_STATUS:"failed"}|<p>-</p>|`3`|
|{$VDISK_WARN_STATUS:"degraded"}|<p>-</p>|`4`|

## Template links

|Name|
|----|
|Template Module Generic SNMPv2|

## Discovery rules

|Name|Description|Type|Key and additional info|
|----|-----------|----|----|
|Temperature CPU Discovery|<p>Scanning table of Temperature Probe Table IDRAC-MIB-SMIv2::temperatureProbeTable</p>|SNMP|temp.cpu.discovery<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `.*CPU.*`</p>|
|Temperature Ambient Discovery|<p>Scanning table of Temperature Probe Table IDRAC-MIB-SMIv2::temperatureProbeTable</p>|SNMP|temp.ambient.discovery<p>**Filter**:</p>AND_OR <p>- A: {#SENSOR_LOCALE} MATCHES_REGEX `.*Inlet Temp.*`</p>|
|PSU Discovery|<p>IDRAC-MIB-SMIv2::powerSupplyTable</p>|SNMP|psu.discovery|
|FAN Discovery|<p>IDRAC-MIB-SMIv2::coolingDeviceTable</p>|SNMP|fan.discovery<p>**Filter**:</p>AND_OR <p>- A: {#TYPE} MATCHES_REGEX `3`</p>|
|Physical Disk Discovery|<p>IDRAC-MIB-SMIv2::physicalDiskTable</p>|SNMP|physicaldisk.discovery|
|Virtual Disk Discovery|<p>IDRAC-MIB-SMIv2::virtualDiskTable</p>|SNMP|virtualdisk.discovery|
|Array Controller Discovery|<p>IDRAC-MIB-SMIv2::controllerTable</p>|SNMP|physicaldisk.arr.discovery|
|Array Controller Cache Discovery|<p>IDRAC-MIB-SMIv2::batteryTable</p>|SNMP|array.cache.discovery|

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|Disk_arrays|{#CNTLR_NAME}: Disk array controller status|<p>MIB: IDRAC-MIB-SMIv2</p><p>The status of the controller itself without the propagation of any contained component status.</p><p>Possible values:</p><p>1: Other</p><p>2: Unknown</p><p>3: OK</p><p>4: Non-critical</p><p>5: Critical</p><p>6: Non-recoverable</p><p>                </p>|SNMP|system.hw.diskarray.status[controllerComponentStatus.{#SNMPINDEX}]|
|Disk_arrays|{#CNTLR_NAME}: Disk array controller model|<p>MIB: IDRAC-MIB-SMIv2</p><p>The controller's name as represented in Storage Management.</p>|SNMP|system.hw.diskarray.model[controllerName.{#SNMPINDEX}]|
|Disk_arrays|Battery {#BATTERY_NUM}: Disk array cache controller battery status|<p>MIB: IDRAC-MIB-SMIv2</p><p>Current state of battery.</p><p>Possible values:</p><p>1: The current state could not be determined.</p><p>2: The battery is operating normally.</p><p>3: The battery has failed and needs to be replaced.</p><p>4: The battery temperature is high or charge level is depleting.</p><p>5: The battery is missing or not detected.</p><p>6: The battery is undergoing the re-charge phase.</p><p>7: The battery voltage or charge level is below the threshold.</p><p>                </p>|SNMP|system.hw.diskarray.cache.battery.status[batteryState.{#SNMPINDEX}]|
|Fans|{#FAN_DESCR}: Fan status|<p>MIB: IDRAC-MIB-SMIv2</p><p>0700.0012.0001.0005 This attribute defines the probe status of the cooling device.</p>|SNMP|sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}]|
|Fans|{#FAN_DESCR}: Fan speed|<p>MIB: IDRAC-MIB-SMIv2</p><p>0700.0012.0001.0006 This attribute defines the reading for a cooling device</p><p>of subtype other than coolingDeviceSubTypeIsDiscrete.  When the value</p><p>for coolingDeviceSubType is other than coolingDeviceSubTypeIsDiscrete, the</p><p>value returned for this attribute is the speed in RPM or the OFF/ON value</p><p>of the cooling device.  When the value for coolingDeviceSubType is</p><p>coolingDeviceSubTypeIsDiscrete, a value is not returned for this attribute.</p>|SNMP|sensor.fan.speed[coolingDeviceReading.{#SNMPINDEX}]|
|Inventory|Hardware model name|<p>MIB: IDRAC-MIB-SMIv2</p><p>This attribute defines the model name of the system.</p>|SNMP|system.hw.model<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Operating system|<p>MIB: IDRAC-MIB-SMIv2</p><p>This attribute defines the name of the operating system that the hostis running.</p>|SNMP|system.sw.os[systemOSName]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Hardware serial number|<p>MIB: IDRAC-MIB-SMIv2</p><p>This attribute defines the service tag of the system.</p>|SNMP|system.hw.serialnumber<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Inventory|Firmware version|<p>MIB: IDRAC-MIB-SMIv2</p><p>This attribute defines the firmware version of a remote access card.</p>|SNMP|system.hw.firmware<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p>|
|Physical_disks|{#DISK_NAME}: Physical disk status|<p>MIB: IDRAC-MIB-SMIv2</p><p>The status of the physical disk itself without the propagation of any contained component status.</p><p>Possible values:</p><p>1: Other</p><p>2: Unknown</p><p>3: OK</p><p>4: Non-critical</p><p>5: Critical</p><p>6: Non-recoverable</p>|SNMP|system.hw.physicaldisk.status[physicalDiskComponentStatus.{#SNMPINDEX}]|
|Physical_disks|{#DISK_NAME}: Physical disk serial number|<p>MIB: IDRAC-MIB-SMIv2</p><p>The physical disk's unique identification number from the manufacturer.</p>|SNMP|system.hw.physicaldisk.serialnumber[physicalDiskSerialNo.{#SNMPINDEX}]|
|Physical_disks|{#DISK_NAME}: Physical disk S.M.A.R.T. status|<p>MIB: IDRAC-MIB-SMIv2</p><p>Indicates whether the physical disk has received a predictive failure alert.</p>|SNMP|system.hw.physicaldisk.smart_status[physicalDiskSmartAlertIndication.{#SNMPINDEX}]|
|Physical_disks|{#DISK_NAME}: Physical disk model name|<p>MIB: IDRAC-MIB-SMIv2</p><p>The model number of the physical disk.</p>|SNMP|system.hw.physicaldisk.model[physicalDiskProductID.{#SNMPINDEX}]|
|Physical_disks|{#DISK_NAME}: Physical disk part number|<p>MIB: IDRAC-MIB-SMIv2</p><p>The part number of the disk.</p>|SNMP|system.hw.physicaldisk.part_number[physicalDiskPartNumber.{#SNMPINDEX}]|
|Physical_disks|{#DISK_NAME}: Physical disk media type|<p>MIB: IDRAC-MIB-SMIv2</p><p>The media type of the physical disk. Possible Values:</p><p>1: The media type could not be determined.</p><p>2: Hard Disk Drive (HDD).</p><p>3: Solid State Drive (SSD).</p>|SNMP|system.hw.physicaldisk.media_type[physicalDiskMediaType.{#SNMPINDEX}]|
|Physical_disks|{#DISK_NAME}: Disk size|<p>MIB: IDRAC-MIB-SMIv2</p><p>The size of the physical disk in megabytes.</p>|SNMP|system.hw.physicaldisk.size[physicalDiskCapacityInMB.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1048576`</p>|
|Power_supply|{#PSU_DESCR}: Power supply status|<p>MIB: IDRAC-MIB-SMIv2</p><p>0600.0012.0001.0005 This attribute defines the status of the power supply.</p>|SNMP|sensor.psu.status[powerSupplyStatus.{#SNMPINDEX}]|
|Status|Overall system health status|<p>MIB: IDRAC-MIB-SMIv2</p><p>This attribute defines the overall rollup status of all components in the system being monitored by the remote access card. Includes system, storage, IO devices, iDRAC, CPU, memory, etc.</p>|SNMP|system.status[globalSystemStatus.0]|
|Temperature|{#SENSOR_LOCALE}: Temperature|<p>MIB: IDRAC-MIB-SMIv2</p><p>0700.0020.0001.0006 This attribute defines the reading for a temperature probe of type other than temperatureProbeTypeIsDiscrete.  When the value for temperatureProbeType is other than temperatureProbeTypeIsDiscrete,the value returned for this attribute is the temperature that the probeis reading in tenths of degrees Centigrade. When the value for temperatureProbeType is temperatureProbeTypeIsDiscrete, a value is not returned for this attribute.</p>|SNMP|sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `0.1`</p>|
|Temperature|{#SENSOR_LOCALE}: Temperature status|<p>MIB: IDRAC-MIB-SMIv2</p><p>0700.0020.0001.0005 This attribute defines the probe status of the temperature probe.</p>|SNMP|sensor.temp.status[temperatureProbeStatus.CPU.{#SNMPINDEX}]|
|Temperature|{#SENSOR_LOCALE}: Temperature|<p>MIB: IDRAC-MIB-SMIv2</p><p>0700.0020.0001.0006 This attribute defines the reading for a temperature probe of type other than temperatureProbeTypeIsDiscrete.  When the value for temperatureProbeType is other than temperatureProbeTypeIsDiscrete,the value returned for this attribute is the temperature that the probeis reading in tenths of degrees Centigrade. When the value for temperatureProbeType is temperatureProbeTypeIsDiscrete, a value is not returned for this attribute.</p>|SNMP|sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `0.1`</p>|
|Temperature|{#SENSOR_LOCALE}: Temperature status|<p>MIB: IDRAC-MIB-SMIv2</p><p>0700.0020.0001.0005 This attribute defines the probe status of the temperature probe.</p>|SNMP|sensor.temp.status[temperatureProbeStatus.Ambient.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPVALUE}({#DISK_NAME}): Layout type |<p>MIB: IDRAC-MIB-SMIv2</p><p>The virtual disk's RAID type.</p><p>Possible values:</p><p>1: Not one of the following</p><p>2: RAID-0</p><p>3: RAID-1</p><p>4: RAID-5</p><p>5: RAID-6</p><p>6: RAID-10</p><p>7: RAID-50</p><p>8: RAID-60</p><p>9: Concatenated RAID 1</p><p>10: Concatenated RAID 5</p>|SNMP|system.hw.virtualdisk.layout[virtualDiskLayout.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPVALUE}({#DISK_NAME}): Current state|<p>MIB: IDRAC-MIB-SMIv2</p><p>The state of the virtual disk when there are progressive operations ongoing.</p><p>Possible values:</p><p>1: There is no active operation running.</p><p>2: The virtual disk configuration has changed. The physical disks included in the virtual disk are being modified to support the new configuration.</p><p>3: A Consistency Check (CC) is being performed on the virtual disk.</p><p>4: The virtual disk is being initialized.</p><p>5: BackGround Initialization (BGI) is being performed on the virtual disk.</p>|SNMP|system.hw.virtualdisk.state[virtualDiskOperationalState.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPVALUE}({#DISK_NAME}): Read policy|<p>MIB: IDRAC-MIB-SMIv2</p><p>The read policy used by the controller for read operations on this virtual disk.</p><p>Possible values:</p><p>1: No Read Ahead.</p><p>2: Read Ahead.</p><p>3: Adaptive Read Ahead.</p>|SNMP|system.hw.virtualdisk.readpolicy[virtualDiskReadPolicy.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPVALUE}({#DISK_NAME}): Write policy|<p>MIB: IDRAC-MIB-SMIv2</p><p>The write policy used by the controller for write operations on this virtual disk.</p><p>Possible values:</p><p>1: Write Through.</p><p>2: Write Back.</p><p>3: Force Write Back.</p>|SNMP|system.hw.virtualdisk.writepolicy[virtualDiskWritePolicy.{#SNMPINDEX}]|
|Virtual_disks|Disk {#SNMPVALUE}({#DISK_NAME}): Disk size|<p>MIB: IDRAC-MIB-SMIv2</p><p>The size of the virtual disk in megabytes.</p>|SNMP|system.hw.virtualdisk.size[virtualDiskSizeInMB.{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1048576`</p>|
|Virtual_disks|Disk {#SNMPVALUE}({#DISK_NAME}): Status|<p>MIB: IDRAC-MIB-SMIv2</p><p>The current state of this virtual disk (which includes any member physical disks.)</p><p>Possible states:</p><p>1: The current state could not be determined.</p><p>2: The virtual disk is operating normally or optimally.</p><p>3: The virtual disk has encountered a failure. The data on disk is lost or is about to be lost.</p><p>4: The virtual disk encounterd a failure with one or all of the constituent redundant physical disks.</p><p>The data on the virtual disk might no longer be fault tolerant.</p>|SNMP|system.hw.virtualdisk.status[virtualDiskState.{#SNMPINDEX}]|

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|{#CNTLR_NAME}: Disk array controller is in unrecoverable state!|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.status[controllerComponentStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_FAIL_STATUS:"nonRecoverable"},eq)}=1`|DISASTER||
|{#CNTLR_NAME}: Disk array controller is in critical state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.status[controllerComponentStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CRIT_STATUS:"critical"},eq)}=1`|HIGH|<p>**Depends on**:</p><p>- {#CNTLR_NAME}: Disk array controller is in unrecoverable state!</p>|
|{#CNTLR_NAME}: Disk array controller is in warning state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.status[controllerComponentStatus.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_WARN_STATUS:"nonCritical"},eq)}=1`|AVERAGE|<p>**Depends on**:</p><p>- {#CNTLR_NAME}: Disk array controller is in critical state</p><p>- {#CNTLR_NAME}: Disk array controller is in unrecoverable state!</p>|
|Battery {#BATTERY_NUM}: Disk array cache controller battery is in warning state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[batteryState.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_WARN_STATUS},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- Battery {#BATTERY_NUM}: Disk array cache controller battery is in critical state!</p>|
|Battery {#BATTERY_NUM}: Disk array cache controller battery is not in optimal state|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[batteryState.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_OK_STATUS},ne)}=1`|WARNING|<p>**Depends on**:</p><p>- Battery {#BATTERY_NUM}: Disk array cache controller battery is in critical state!</p><p>- Battery {#BATTERY_NUM}: Disk array cache controller battery is in warning state</p>|
|Battery {#BATTERY_NUM}: Disk array cache controller battery is in critical state!|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.hw.diskarray.cache.battery.status[batteryState.{#SNMPINDEX}].count(#1,{$DISK_ARRAY_CACHE_BATTERY_CRIT_STATUS},eq)}=1`|AVERAGE||
|{#FAN_DESCR}: Fan is in critical state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS:"criticalUpper"},eq)}=1 or {TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS:"nonRecoverableUpper"},eq)}=1 or {TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS:"criticalLower"},eq)}=1 or {TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS:"nonRecoverableLower"},eq)}=1 or {TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_CRIT_STATUS:"failed"},eq)}=1`|AVERAGE||
|{#FAN_DESCR}: Fan is in warning state|<p>Please check the fan unit</p>|`{TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_WARN_STATUS:"nonCriticalUpper"},eq)}=1 or {TEMPLATE_NAME:sensor.fan.status[coolingDeviceStatus.{#SNMPINDEX}].count(#1,{$FAN_WARN_STATUS:"nonCriticalLower"},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- {#FAN_DESCR}: Fan is in critical state</p>|
|Operating system description has changed|<p>Operating system description has changed. Possible reasons that system has been updated or replaced. Ack to close.</p>|`{TEMPLATE_NAME:system.sw.os[systemOSName].diff()}=1 and {TEMPLATE_NAME:system.sw.os[systemOSName].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Device has been replaced (new serial number received)|<p>Device serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.serialnumber.diff()}=1 and {TEMPLATE_NAME:system.hw.serialnumber.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|Firmware has changed|<p>Firmware version has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.firmware.diff()}=1 and {TEMPLATE_NAME:system.hw.firmware.strlen()}>0`|INFO|<p>Manual close: YES</p>|
|{#DISK_NAME}: Physical disk failed|<p>Please check physical disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.status[physicalDiskComponentStatus.{#SNMPINDEX}].count(#1,{$DISK_FAIL_STATUS:"critical"},eq)}=1 or {TEMPLATE_NAME:system.hw.physicaldisk.status[physicalDiskComponentStatus.{#SNMPINDEX}].count(#1,{$DISK_FAIL_STATUS:"nonRecoverable"},eq)}=1`|HIGH||
|{#DISK_NAME}: Physical disk is in warning state|<p>Please check physical disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.status[physicalDiskComponentStatus.{#SNMPINDEX}].count(#1,{$DISK_WARN_STATUS:"nonCritical"},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- {#DISK_NAME}: Physical disk failed</p>|
|{#DISK_NAME}: Disk has been replaced (new serial number received)|<p>Disk serial number has changed. Ack to close</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.serialnumber[physicalDiskSerialNo.{#SNMPINDEX}].diff()}=1 and {TEMPLATE_NAME:system.hw.physicaldisk.serialnumber[physicalDiskSerialNo.{#SNMPINDEX}].strlen()}>0`|INFO|<p>Manual close: YES</p>|
|{#DISK_NAME}: Physical disk S.M.A.R.T. failed|<p>Disk probably requires replacement.</p>|`{TEMPLATE_NAME:system.hw.physicaldisk.smart_status[physicalDiskSmartAlertIndication.{#SNMPINDEX}].count(#1,{$DISK_SMART_FAIL_STATUS},eq)}=1`|HIGH|<p>**Depends on**:</p><p>- {#DISK_NAME}: Physical disk failed</p>|
|{#PSU_DESCR}: Power supply is in critical state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[powerSupplyStatus.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS:"critical"},eq)}=1 or {TEMPLATE_NAME:sensor.psu.status[powerSupplyStatus.{#SNMPINDEX}].count(#1,{$PSU_CRIT_STATUS:"nonRecoverable"},eq)}=1`|AVERAGE||
|{#PSU_DESCR}: Power supply is in warning state|<p>Please check the power supply unit for errors</p>|`{TEMPLATE_NAME:sensor.psu.status[powerSupplyStatus.{#SNMPINDEX}].count(#1,{$PSU_WARN_STATUS:"nonCritical"},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- {#PSU_DESCR}: Power supply is in critical state</p>|
|System is in unrecoverable state!|<p>Please check the device for faults</p>|`{TEMPLATE_NAME:system.status[globalSystemStatus.0].count(#1,{$HEALTH_DISASTER_STATUS},eq)}=1`|HIGH||
|System status is in critical state|<p>Please check the device for errors</p>|`{TEMPLATE_NAME:system.status[globalSystemStatus.0].count(#1,{$HEALTH_CRIT_STATUS},eq)}=1`|HIGH|<p>**Depends on**:</p><p>- System is in unrecoverable state!</p>|
|System status is in warning state|<p>Please check the device for warnings</p>|`{TEMPLATE_NAME:system.status[globalSystemStatus.0].count(#1,{$HEALTH_WARN_STATUS},eq)}=1`|WARNING|<p>**Depends on**:</p><p>- System is in unrecoverable state!</p><p>- System status is in critical state</p>|
|{#SENSOR_LOCALE}: Temperature is above warning threshold: >{$TEMP_WARN:"CPU"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"CPU"} or {Template Server Dell iDRAC SNMPv2:sensor.temp.status[temperatureProbeStatus.CPU.{#SNMPINDEX}].last(0)}={$TEMP_WARN_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"CPU"}-3`|WARNING|<p>**Depends on**:</p><p>- {#SENSOR_LOCALE}: Temperature is above critical threshold: >{$TEMP_CRIT:"CPU"}</p>|
|{#SENSOR_LOCALE}: Temperature is above critical threshold: >{$TEMP_CRIT:"CPU"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"CPU"} or {Template Server Dell iDRAC SNMPv2:sensor.temp.status[temperatureProbeStatus.CPU.{#SNMPINDEX}].last(0)}={$TEMP_CRIT_STATUS} or {Template Server Dell iDRAC SNMPv2:sensor.temp.status[temperatureProbeStatus.CPU.{#SNMPINDEX}].last(0)}={$TEMP_DISASTER_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"CPU"}-3`|HIGH||
|{#SENSOR_LOCALE}: Temperature is too low: <{$TEMP_CRIT_LOW:"CPU"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"CPU"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.CPU.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"CPU"}+3`|AVERAGE||
|{#SENSOR_LOCALE}: Temperature is above warning threshold: >{$TEMP_WARN:"Ambient"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}].avg(5m)}>{$TEMP_WARN:"Ambient"} or {Template Server Dell iDRAC SNMPv2:sensor.temp.status[temperatureProbeStatus.Ambient.{#SNMPINDEX}].last(0)}={$TEMP_WARN_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}].max(5m)}<{$TEMP_WARN:"Ambient"}-3`|WARNING|<p>**Depends on**:</p><p>- {#SENSOR_LOCALE}: Temperature is above critical threshold: >{$TEMP_CRIT:"Ambient"}</p>|
|{#SENSOR_LOCALE}: Temperature is above critical threshold: >{$TEMP_CRIT:"Ambient"}|<p>This trigger uses temperature sensor values as well as temperature sensor status if available</p>|`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}].avg(5m)}>{$TEMP_CRIT:"Ambient"} or {Template Server Dell iDRAC SNMPv2:sensor.temp.status[temperatureProbeStatus.Ambient.{#SNMPINDEX}].last(0)}={$TEMP_CRIT_STATUS} or {Template Server Dell iDRAC SNMPv2:sensor.temp.status[temperatureProbeStatus.Ambient.{#SNMPINDEX}].last(0)}={$TEMP_DISASTER_STATUS}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}].max(5m)}<{$TEMP_CRIT:"Ambient"}-3`|HIGH||
|{#SENSOR_LOCALE}: Temperature is too low: <{$TEMP_CRIT_LOW:"Ambient"}|<p>-</p>|`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}].avg(5m)}<{$TEMP_CRIT_LOW:"Ambient"}`<p>Recovery expression:</p>`{TEMPLATE_NAME:sensor.temp.value[temperatureProbeReading.Ambient.{#SNMPINDEX}].min(5m)}>{$TEMP_CRIT_LOW:"Ambient"}+3`|AVERAGE||
|Disk {#SNMPVALUE}({#DISK_NAME}): Virtual disk failed|<p>Please check virtual disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.virtualdisk.status[virtualDiskState.{#SNMPINDEX}].count(#1,{$VDISK_CRIT_STATUS:"failed"},eq)}=1`|HIGH||
|Disk {#SNMPVALUE}({#DISK_NAME}): Virtual disk is in warning state|<p>Please check virtual disk for warnings or errors</p>|`{TEMPLATE_NAME:system.hw.virtualdisk.status[virtualDiskState.{#SNMPINDEX}].count(#1,{$VDISK_WARN_STATUS:"degraded"},eq)}=1`|AVERAGE|<p>**Depends on**:</p><p>- Disk {#SNMPVALUE}({#DISK_NAME}): Virtual disk failed</p>|

## Feedback

Please report any issues with the template at https://support.zabbix.com

