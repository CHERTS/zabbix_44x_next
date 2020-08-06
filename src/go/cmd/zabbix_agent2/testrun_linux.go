/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package main

import (
	"zabbix.com/internal/agent/scheduler"
)

func checkMetrics(s scheduler.Scheduler) {
	metrics := []string{
		`agent.hostname`,
		`agent.ping`,
		`agent.version`,
		`system.localtime[utc]`,
		`system.run[echo test]`,
		`web.page.get[localhost,,80]`,
		`web.page.perf[localhost,,80]`,
		`web.page.regexp[localhost,,80,OK]`,
		`vfs.file.size[/etc/passwd]`,
		`vfs.file.time[/etc/passwd,modify]`,
		`vfs.file.exists[/etc/passwd]`,
		`vfs.file.contents[/etc/passwd]`,
		`vfs.file.regexp[/etc/passwd,root]`,
		`vfs.file.regmatch[/etc/passwd,root]`,
		`vfs.file.md5sum[/etc/passwd]`,
		`vfs.file.cksum[/etc/passwd]`,
		`vfs.dir.size[/var/log]`,
		`vfs.dir.count[/var/log]`,
		`net.dns[,zabbix.com]`,
		`net.dns.record[,zabbix.com]`,
		`net.tcp.dns[,zabbix.com]`,
		`net.tcp.dns.query[,zabbix.com]`,
		`net.tcp.port[,80]`,
		`system.users.num`,
		`log[logfile]`,
		`log.count[logfile]`,
		`logrt[logfile]`,
		`logrt.count[logfile]`,
		`zabbix.stats[127.0.0.1,10051]`,
		`kernel.maxfiles`,
		`kernel.maxproc`,
		`vfs.fs.size[/,free]`,
		`vfs.fs.inode[/,free]`,
		`vfs.fs.discovery`,
		`vfs.dev.write[sda,operations]`,
		`net.tcp.listen[80]`,
		`net.udp.listen[68]`,
		`net.if.in[lo,bytes]`,
		`net.if.out[lo,bytes]`,
		`net.if.total[lo,bytes]`,
		`net.if.collisions[lo]`,
		`net.if.discovery`,
		`vm.memory.size[total]`,
		`proc.cpu.util[inetd]`,
		`proc.num[inetd]`,
		`proc.mem[inetd]`,
		`system.cpu.switches`,
		`system.cpu.intr`,
		`system.cpu.util[all,user,avg1]`,
		`system.cpu.load[all,avg1]`,
		`system.cpu.num[online]`,
		`system.cpu.discovery`,
		`system.uname`,
		`system.hw.chassis`,
		`system.hw.cpu`,
		`system.hw.devices`,
		`system.hw.macaddr`,
		`system.sw.arch`,
		`system.sw.os`,
		`system.sw.packages`,
		`system.swap.size[all,free]`,
		`system.swap.in[all]`,
		`system.swap.out[all]`,
		`system.uptime`,
		`system.boottime`,
		`sensor[w83781d-i2c-0-2d,temp1]`,
		`net.tcp.service[ssh,127.0.0.1,22]`,
		`net.tcp.service.perf[ssh,127.0.0.1,22]`,
		`net.udp.service[ntp,127.0.0.1,123]`,
		`net.udp.service.perf[ntp,127.0.0.1,123]`,
		`system.hostname`,
	}

	for _, metric := range metrics {
		checkMetric(s, metric)
	}
}
