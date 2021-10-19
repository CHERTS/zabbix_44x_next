/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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

package vfsfs

import (
	"syscall"

	"golang.org/x/sys/windows"
	"zabbix.com/pkg/plugin"
)

func getMountPaths() (paths []string, err error) {
	buffer := make([]uint16, windows.MAX_PATH+1)
	volume := make([]uint16, windows.MAX_PATH+1)
	var h windows.Handle
	if h, err = windows.FindFirstVolume(&volume[0], uint32(len(volume))); err != nil {
		return
	}
	defer windows.FindClose(h)

	var result []string
	var size uint32
	for {
		for {
			if err = windows.GetVolumePathNamesForVolumeName(&volume[0], &buffer[0], uint32(len(buffer)), &size); err != nil {
				if err.(syscall.Errno) != syscall.ERROR_MORE_DATA {
					return
				}
				buffer = make([]uint16, size)
			} else {
				break
			}
		}

		buf := buffer
		for buf[0] != 0 {
			result = append(result, windows.UTF16ToString(buf))
			for i, c := range buf {
				if c == 0 {
					buf = buf[i+1:]
					break
				}
			}
		}

		if err = windows.FindNextVolume(h, &volume[0], uint32(len(volume))); err != nil {
			if err.(syscall.Errno) == syscall.ERROR_NO_MORE_FILES {
				break
			}
			return
		}

	}
	return result, nil
}

func getFsInfo(path string) (fsname, fstype, drivetype string, err error) {
	fsname = path
	if len(fsname) > 0 && fsname[len(fsname)-1] == '\\' {
		fsname = fsname[:len(fsname)-1]
	}

	if len(path) >= windows.MAX_PATH && path[:4] != `\\?\` {
		path = `\\?\` + path
	}

	wpath := windows.StringToUTF16Ptr(path)
	buf := make([]uint16, windows.MAX_PATH+1)
	if err = windows.GetVolumeInformation(wpath, nil, 0, nil, nil, nil, &buf[0], uint32(len(buf))); err != nil {
		fstype = "UNKNOWN"
	} else {
		fstype = windows.UTF16ToString(buf)
	}

	dt := windows.GetDriveType(wpath)
	switch dt {
	case windows.DRIVE_UNKNOWN:
		drivetype = "unknown"
	case windows.DRIVE_NO_ROOT_DIR:
		drivetype = "norootdir"
	case windows.DRIVE_REMOVABLE:
		drivetype = "removable"
	case windows.DRIVE_FIXED:
		drivetype = "fixed"
	case windows.DRIVE_REMOTE:
		drivetype = "remote"
	case windows.DRIVE_CDROM:
		drivetype = "cdrom"
	case windows.DRIVE_RAMDISK:
		drivetype = "ramdisk"
	default:
		drivetype = "unknown"
	}
	return
}

func getFsStats(path string) (stats *FsStats, err error) {
	var totalFree, callerFree, total uint64
	if err = windows.GetDiskFreeSpaceEx(windows.StringToUTF16Ptr(path), &callerFree, &total, &totalFree); err != nil {
		return
	}
	totalUsed := total - totalFree
	stats = &FsStats{
		Total: total,
		Free:  totalFree,
		Used:  totalUsed,
		PFree: float64(totalFree) / float64(total) * 100,
		PUsed: float64(totalUsed) / float64(total) * 100,
	}
	return
}

func (p *Plugin) getFsInfo() (data []*FsInfo, err error) {
	var paths []string
	if paths, err = getMountPaths(); err != nil {
		return
	}
	for _, path := range paths {
		if fsname, fstype, drivetype, fserr := getFsInfo(path); fserr == nil {
			data = append(data, &FsInfo{
				FsName:    &fsname,
				FsType:    &fstype,
				DriveType: &drivetype,
			})
		} else {
			p.Debugf(`cannot obtain file system information for "%s": %s`, path, fserr)
		}
	}
	return
}

func (p *Plugin) getFsInfoStats() (data []*FsInfoNew, err error) {
	var paths []string
	if paths, err = getMountPaths(); err != nil {
		return
	}
	fsmap := make(map[string]*FsInfoNew)
	for _, path := range paths {
		var info FsInfoNew
		if fsname, fstype, drivetype, fserr := getFsInfo(path); fserr == nil {
			info.FsName = &fsname
			info.FsType = &fstype
			info.DriveType = &drivetype
		} else {
			p.Debugf(`cannot obtain file system information for "%s": %s`, path, fserr)
			continue
		}
		if stats, fserr := getFsStats(path); err == nil {
			info.Bytes = stats
			fsmap[path] = &info
		} else {
			p.Debugf(`cannot obtain file system statistics for "%s": %s`, path, fserr)
			continue
		}
	}
	if paths, err = getMountPaths(); err != nil {
		return
	}
	for _, path := range paths {
		if info, ok := fsmap[path]; ok {
			data = append(data, info)
		}
	}
	return
}

func getFsInode(string) (*FsStats, error) {
	return nil, plugin.UnsupportedMetricError
}

func init() {
	plugin.RegisterMetrics(&impl, "VfsFs",
		"vfs.fs.discovery", "List of mounted filesystems. Used for low-level discovery.",
		"vfs.fs.get", "List of mounted filesystems with statistics.",
		"vfs.fs.size", "Disk space in bytes or in percentage from total.",
	)
}
