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

package log

import (
	"bytes"
	"errors"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"runtime"
	"runtime/debug"
	"sync"
)

const Info = 0
const Crit = 1
const Err = 2
const Warning = 3
const Debug = 4
const Trace = 5

const None = -1

const Undefined = 0
const System = 1
const File = 2
const Console = 3

const MB = 1048576

var logLevel int
var logger *log.Logger

type LogStat struct {
	logType  int
	filename string
	filesize int64
	f        *os.File
}

var logStat LogStat
var logAccess sync.Mutex

func CheckLogLevel(level int) bool {
	if level > logLevel {
		return false
	}
	return true
}

func Level() string {
	switch logLevel {
	case None:
		return "none"
	case Info:
		return "info"
	case Crit:
		return "critical"
	case Err:
		return "error"
	case Warning:
		return "warning"
	case Debug:
		return "debug"
	case Trace:
		return "trace"
	default:
		return "unknown"
	}
}

func IncreaseLogLevel() (success bool) {
	if logLevel != Trace {
		logLevel++
		return true
	}
	return false
}

func DecreaseLogLevel() (success bool) {
	if logLevel != Info {
		logLevel--
		return true
	}
	return false
}

func Open(logType int, level int, filename string, filesize int) error {

	logStat.logType = logType
	logStat.filename = filename
	logStat.filesize = int64(filesize) * MB
	var err error

	if logType == Console {
		logger = log.New(os.Stdout, "", log.Lmicroseconds|log.Ldate)
	} else if logType == File {
		logStat.f, err = os.OpenFile(filename, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
		if err != nil {
			return err
		}
		logger = log.New(logStat.f, "", log.Lmicroseconds|log.Ldate)
	} else {
		return errors.New("invalid argument")
	}

	logLevel = level
	return nil
}

func Infof(format string, args ...interface{}) {
	if CheckLogLevel(Info) {
		procLog(format, args)
	}
}

func Critf(format string, args ...interface{}) {
	if CheckLogLevel(Crit) {
		procLog(format, args)
	}
}

func Errf(format string, args ...interface{}) {
	if CheckLogLevel(Err) {
		procLog(format, args)
	}
}

func Warningf(format string, args ...interface{}) {
	if CheckLogLevel(Warning) {
		procLog(format, args)
	}
}

func Tracef(format string, args ...interface{}) {
	if CheckLogLevel(Trace) {
		procLog(format, args)
	}
}

func Debugf(format string, args ...interface{}) {
	if CheckLogLevel(Debug) {
		procLog(format, args)
	}
}

func procLog(format string, args []interface{}) {
	logAccess.Lock()
	defer logAccess.Unlock()
	rotateLog()
	logger.Printf(format, args...)
}

func rotateLog() {
	if logStat.logType == File && logStat.filesize != 0 {
		var printError string

		fstat, err := os.Stat(logStat.filename)
		if err != nil {
			return
		}

		if fstat.Size() > logStat.filesize {
			filenameOld := logStat.filename + ".old"
			logStat.f.Close()
			os.Remove(filenameOld)

			err = os.Rename(logStat.filename, filenameOld)
			if err != nil {
				printError = err.Error()
			}

			logStat.f, err = os.OpenFile(logStat.filename, os.O_CREATE|os.O_WRONLY, 0644)
			if err != nil {
				errmsg := "Cannot open log file"
				if printError != "" {
					errmsg = fmt.Sprintf("%s and cannot rename it: %s", errmsg, printError)
				}
				logStat.logType = Undefined
				panic(errmsg)
			}

			logger = log.New(logStat.f, "", log.Lmicroseconds|log.Ldate)
			if printError != "" {
				logger.Printf("cannot rename log file \"%s\" to \"%s\":%s\n",
					logStat.filename, filenameOld, printError)
				logger.Printf("Logfile \"%s\" size reached configured limit LogFileSize but"+
					" moving it to \"%s\" failed. The logfile was truncated.",
					logStat.filename, filenameOld)
			}
		}
	}
}

func PanicHook() {
	if r := recover(); r != nil {
		if logStat.logType != Undefined {
			data := debug.Stack()
			Critf("Critical failure: %v", r)
			var tail int
			for offset, end, num := 0, 0, 1; end != -1; offset, num = offset+end+1, num+1 {
				end = bytes.IndexByte(data[offset:], '\n')
				if end != -1 {
					tail = offset + end
				} else {
					tail = len(data)
				}
				Critf("%s", string(data[offset:tail]))
			}
		}
		panic(r)
	}
}

func Caller() (name string) {
	pc := make([]uintptr, 2)
	n := runtime.Callers(2, pc)
	frames := runtime.CallersFrames(pc[:n])
	if frame, ok := frames.Next(); ok {
		return filepath.Base(frame.Func.Name())
	}
	return ""
}
