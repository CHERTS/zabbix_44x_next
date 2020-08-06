// +build windows

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

package file

import (
	"errors"
	"fmt"
	"os"
	"syscall"
	"unsafe"

	"golang.org/x/sys/windows"
)

const (
	fileBasicInfo = 0 // FILE_BASIC_INFO
)

type FILE_BASIC_INFO struct {
	CreationTime   windows.Filetime
	LastAccessTime windows.Filetime
	LastWriteTime  windows.Filetime
	ChangeTime     windows.Filetime
	FileAttributes uint32
	// padding
	_ uint32
}

// Export -
func (p *Plugin) exportTime(params []string) (result interface{}, err error) {
	if len(params) > 2 || len(params) == 0 {
		return nil, errors.New("Invalid number of parameters.")
	}
	if "" == params[0] {
		return nil, errors.New("Invalid first parameter.")
	}

	if len(params) == 1 || params[1] == "" || params[1] == "modify" {
		if fi, ferr := os.Stat(params[0]); ferr != nil {
			return nil, fmt.Errorf("Cannot stat file: %s", ferr)
		} else {
			return fi.ModTime().Unix(), nil
		}
	} else if params[1] == "access" {
		if fi, ferr := os.Stat(params[0]); ferr != nil {
			return nil, fmt.Errorf("Cannot stat file: %s", ferr)
		} else {
			if stat, ok := fi.Sys().(*syscall.Win32FileAttributeData); !ok {
				return nil, errors.New("Invalid system data returned by stat.")
			} else {
				return stat.LastAccessTime.Nanoseconds() / 1e9, nil
			}
		}
	} else if params[1] == "change" {
		var f *os.File
		if f, err = os.Open(params[0]); err != nil {
			return nil, fmt.Errorf("Cannot open file: %s", err)
		}
		defer f.Close()

		var bi FILE_BASIC_INFO
		err = windows.GetFileInformationByHandleEx(windows.Handle(f.Fd()), fileBasicInfo, (*byte)(unsafe.Pointer(&bi)),
			uint32(unsafe.Sizeof(bi)))

		if err != nil {
			return nil, fmt.Errorf("Cannot obtain file information: %s", err)
		}
		return bi.ChangeTime.Nanoseconds() / 1e9, nil
	} else {
		return nil, errors.New("Invalid second parameter.")
	}

}
