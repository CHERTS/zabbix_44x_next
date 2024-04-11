# ===========================================================================
#        http://www.nongnu.org/autoconf-archive/ax_lib_oracle_oci.html
# ===========================================================================
#
# SYNOPSIS
#
#   AX_LIB_ORACLE_OCI([MINIMUM-VERSION])
#
# DESCRIPTION
#
#   This macro provides tests of availability of Oracle OCI API of
#   particular version or newer. This macros checks for Oracle OCI headers
#   and libraries and defines compilation flags.
#
#   Macro supports following options and their values:
#
#   1) Single-option usage:
#
#       --with-oracle         -- path to ORACLE_HOME directory
#
#   2) Two-options usage (both options are required):
#
#       --with-oracle-include -- path to directory with OCI headers
#       --with-oracle-lib     -- path to directory with OCI libraries
#
#   NOTE: These options described above do not take yes|no values. If 'yes'
#   value is passed, then WARNING message will be displayed, 'no' value, as
#   well as the --without-oracle-* variations will cause the macro to not
#   check anything.
#
#   This macro calls:
#
#     AC_SUBST(ORACLE_OCI_CFLAGS)
#     AC_SUBST(ORACLE_OCI_LDFLAGS)
#     AC_SUBST(ORACLE_OCI_LIBS)
#     AC_SUBST(ORACLE_OCI_VERSION)
#
#   And sets:
#
#     HAVE_ORACLE_OCI
#
# LICENSE
#
#   Copyright (c) 2008 Mateusz Loskot <mateusz@loskot.net>
#
#   Copying and distribution of this file, with or without modification, are
#   permitted in any medium without royalty provided the copyright notice
#   and this notice are preserved.
#
# ADAPTATION
#
#   Macro adapted for ZABBIX usage by Eugene Grigorjev
#

AC_DEFUN([AX_LIB_ORACLE_OCI],
[
    AC_ARG_WITH([oracle],
        AC_HELP_STRING([--with-oracle@<:@=ARG@:>@],
            [use Oracle OCI API from given Oracle home (ARG=path); use existing ORACLE_HOME (ARG=yes); disable Oracle OCI support (ARG=no)]
        ),
        [
        if test "$withval" = "no"; then
            want_oracle_oci="no"
        elif test "$withval" = "yes"; then
            want_oracle_oci="yes"
            oracle_home_dir="$ORACLE_HOME"
        elif test -d "$withval"; then
            want_oracle_oci="yes"
            oracle_home_dir="$withval"
        else
            want_oracle_oci="yes"
            oracle_home_dir=""
        fi
        ],
        [want_oracle_oci="no"]
    )

    AC_ARG_WITH([oracle-include],
        AC_HELP_STRING([--with-oracle-include@<:@=DIR@:>@],
            [use Oracle OCI API headers from given path]
        ),
        [
        if test "$withval" != "no"; then
            want_oracle_oci="yes"
            oracle_home_include_dir="$withval"
        fi
        ],
        [oracle_home_include_dir=""]
    )
    AC_ARG_WITH([oracle-lib],
        AC_HELP_STRING([--with-oracle-lib@<:@=DIR@:>@],
            [use Oracle OCI API libraries from given path]
        ),
        [
        if test "$withval" != "no"; then
            want_oracle_oci="yes"
            oracle_home_lib_dir="$withval"
        fi
        ],
        [oracle_home_lib_dir=""]
    )

    ORACLE_OCI_CFLAGS=""
    ORACLE_OCI_LDFLAGS=""
    ORACLE_OCI_LIBS=""
    ORACLE_OCI_VERSION=""

    dnl
    dnl Collect include/lib paths
    dnl
    want_oracle_but_no_path="no"

    if test -n "$oracle_home_dir"; then

        if test "$oracle_home_dir" != "no" -a "$oracle_home_dir" != "yes"; then
            dnl ORACLE_HOME path provided

            dnl Primary path to OCI headers, available in Oracle>=10
            oracle_include_dir="$oracle_home_dir/rdbms/public"

            dnl Secondary path to OCI headers used by older versions
            oracle_include_dir2="$oracle_home_dir/rdbms/demo"

            dnl Library path
            oracle_lib_dir="$oracle_home_dir/lib"
        elif test "$oracle_home_dir" = "yes"; then
            want_oracle_but_no_path="yes"
        fi

    elif test -n "$oracle_home_include_dir" -o -n "$oracle_home_lib_dir"; then

        if test "$oracle_home_include_dir" != "no" -a "$oracle_home_include_dir" != "yes"; then
            oracle_include_dir="$oracle_home_include_dir"
        elif test "$oracle_home_include_dir" = "yes"; then
            want_oracle_but_no_path="yes"
        fi

        if test "$oracle_home_lib_dir" != "no" -a "$oracle_home_lib_dir" != "yes"; then
            oracle_lib_dir="$oracle_home_lib_dir"
        elif test "$oracle_home_lib_dir" = "yes"; then
            want_oracle_but_no_path="yes"
        fi
    elif test "$want_oracle_oci" = "yes"; then
        want_oracle_but_no_path="yes"
    fi

    dnl
    dnl Search include/lib paths of Oracle client installed from RPM package
    dnl

    if test "x$want_oracle_but_no_path" = "xyes"; then
        oracle_rpm_include_dir='/usr/include/oracle/*/*'
        oracle_rpm_lib_dir='/usr/lib/oracle/*/*/lib'
        cnt_include=0
        cnt_lib=0

        for d in $oracle_rpm_include_dir; do
            if test -d $d; then
                if test -f "$d/oci.h" -o -f "$d/ociver.h"; then
                    ((cnt_include++))
                    tmp_include_dir=$d
                fi
            fi
        done

        for d in $oracle_rpm_lib_dir; do
            if test -d $d; then
                if test -f "$d/libclntsh.so"; then
                    ((cnt_lib++))
                    tmp_lib_dir=$d
                fi
            fi
        done

        AC_MSG_CHECKING([for Oracle include dir installed from RPM package])
        if test $cnt_include -eq 1; then
            AC_MSG_RESULT([$oracle_include_dir])
            oracle_include_dir=$tmp_include_dir
        else
            AC_MSG_RESULT([no])
            if test $cnt_include -gt 1; then
                AC_MSG_WARN([$cnt_include Oracle include dirs installed from RPM packages, impossible to choose automatically])
            fi
        fi

        AC_MSG_CHECKING([for Oracle lib dir installed from RPM package])
        if test $cnt_lib -eq 1; then
            AC_MSG_RESULT([$oracle_lib_dir])
            oracle_lib_dir=$tmp_lib_dir
        else
            AC_MSG_RESULT([no])
            if test $cnt_lib -gt 1; then
                AC_MSG_WARN([$cnt_lib Oracle lib dirs installed from RPM packages, impossible to choose automatically])
            fi
        fi

        if test $cnt_include -eq 1 && test $cnt_lib -eq 1; then
            want_oracle_but_no_path="no"
        fi
    fi

    if test "$want_oracle_but_no_path" = "yes"; then
        AC_MSG_WARN([Oracle support is requested but no Oracle paths have been provided or automatically detected. \
Please, locate Oracle directories using --with-oracle or \
--with-oracle-include and --with-oracle-lib options.])
    fi

    dnl
    dnl Check OCI files
    dnl
    if test -n "$oracle_include_dir" -a -n "$oracle_lib_dir"; then

        saved_CPPFLAGS="$CPPFLAGS"
        CPPFLAGS="$CPPFLAGS -I$oracle_include_dir"

        dnl Additional path for older Oracle installations
        if test -n "$oracle_include_dir2"; then
            CPPFLAGS="$CPPFLAGS -I$oracle_include_dir2"
        fi

        dnl Depending on later Oracle version detection,
        dnl -lnnz10 flag might be removed for older Oracle < 10.x
        saved_LDFLAGS="$LDFLAGS"
        oci_ldflags="-L$oracle_lib_dir"
        LDFLAGS="$LDFLAGS $oci_ldflags"

        saved_LIBS="$LIBS"
        oci_libs="-lclntsh"
        LIBS="$LIBS $oci_libs"

        dnl
        dnl Check OCI headers
        dnl
        AC_MSG_CHECKING([for Oracle OCI headers in $oracle_include_dir])

        dnl Starting with Oracle version 18c macros OCI_MAJOR_VERSION and OCI_MINOR_VERSION are moved to ociver.h
        if test -f "$oracle_include_dir/ociver.h"; then
            oracle_version_file="ociver.h"
        else
            oracle_version_file="oci.h"
        fi

        AC_COMPILE_IFELSE([
            AC_LANG_PROGRAM([[@%:@include <$oracle_version_file>]],
                [[
#if defined(OCI_MAJOR_VERSION)
#if OCI_MAJOR_VERSION == 10 && OCI_MINOR_VERSION == 2
// Oracle 10.2 detected
#endif
#elif defined(OCI_V7_SYNTAX)
// OK, older Oracle detected
// TODO - mloskot: find better macro to check for older versions;
#else
#  error Oracle $oracle_version_file header not found
#endif
                ]]
            )],
            [
            ORACLE_OCI_CFLAGS="-I$oracle_include_dir"

            if test -n "$oracle_include_dir2"; then
                ORACLE_OCI_CFLAGS="$ORACLE_OCI_CFLAGS -I$oracle_include_dir2"
            fi

            oci_header_found="yes"
            AC_MSG_RESULT([yes])
            ],
            [
            oci_header_found="no"
            AC_MSG_RESULT([not found])
            ]
        )

        dnl
        dnl Check required version of Oracle is available
        dnl
        oracle_version_req=ifelse([$1], [], [], [$1])

        if test "$oci_header_found" = "yes"; then

            oracle_version_major=`cat $oracle_include_dir/$oracle_version_file \
                                 | grep '#define.*OCI_MAJOR_VERSION.*' \
                                 | sed -e 's/#define OCI_MAJOR_VERSION  *//' \
                                 | sed -e 's/  *\/\*.*\*\///'`

            oracle_version_minor=`cat $oracle_include_dir/$oracle_version_file \
                                 | grep '#define.*OCI_MINOR_VERSION.*' \
                                 | sed -e 's/#define OCI_MINOR_VERSION  *//' \
                                 | sed -e 's/  *\/\*.*\*\///'`

            dnl Calculate its number representation
            oracle_version_number=`expr $oracle_version_major \* 1000000 \
                                  \+ $oracle_version_minor \* 1000`


            if test -n "$oracle_version_req"; then
                AC_MSG_CHECKING([if Oracle OCI version is >= $oracle_version_req])

                if test -n "$oracle_version_major" -a -n $"oracle_version_minor"; then

                    ORACLE_OCI_VERSION="$oracle_version_major.$oracle_version_minor"

                    dnl Decompose required version string of Oracle
                    dnl and calculate its number representation
                    oracle_version_req_major=`expr $oracle_version_req : '\([[0-9]]*\)'`
                    oracle_version_req_minor=`expr $oracle_version_req : '[[0-9]]*\.\([[0-9]]*\)'`

                    oracle_version_req_number=`expr $oracle_version_req_major \* 1000000 \
                                               \+ $oracle_version_req_minor \* 1000`

                    oracle_version_check=`expr $oracle_version_number \>\= $oracle_version_req_number`
                    if test "$oracle_version_check" = "1"; then

                        oracle_version_checked="yes"
                        AC_MSG_RESULT([yes])

                    else
                        oracle_version_checked="no"
                        AC_MSG_RESULT([no])
                        AC_MSG_ERROR([Oracle $ORACLE_OCI_VERSION found, but required version is $oracle_version_req])
                    fi
                else
                    ORACLE_OCI_VERSION="UNKNOWN"
                    AC_MSG_RESULT([no])
                    AC_MSG_WARN([Oracle version unknown, probably OCI older than 10.2 is available])
                fi
            fi

            dnl Add -lnnz1x flag to Oracle >= 10.x
            AC_MSG_CHECKING([for Oracle OCI version >= 10.x to use -lnnz1x flag])
            if test "$oracle_version_major" -ge 10; then
                oci_libs="$oci_libs -lnnz$oracle_version_major"
                LIBS="$LIBS -lnnz$oracle_version_major"
                AC_MSG_RESULT([-lnnz$oracle_version_major])
            else
                AC_MSG_RESULT([no])
            fi

            dnl Add -lons -lclntshcore -lmql1 -lipc1 flags to Oracle = 12.x
            AC_MSG_CHECKING([for Oracle OCI version = 12.x to use -lons -lclntshcore -lmql1 -lipc1 flags])
            if test "$oracle_version_major" -eq 12; then
                oci_libs="$oci_libs -lons -lclntshcore -lmql1 -lipc1"
                LIBS="$LIBS -lons -lclntshcore -lmql1 -lipc1"
                AC_MSG_RESULT([yes])
            else
                AC_MSG_RESULT([no])
            fi
        fi

        dnl
        dnl Check OCI libraries
        dnl
        if test "$oci_header_found" = "yes"; then

            AC_MSG_CHECKING([for Oracle OCI libraries in $oracle_lib_dir])

            AC_LINK_IFELSE([
                AC_LANG_PROGRAM([[@%:@include <oci.h>]],
                    [[
OCIEnv* envh = 0;
OCIEnvNlsCreate(&envh, OCI_DEFAULT, 0, 0, 0, 0, 0, 0, 0, 0);
if (envh) OCIHandleFree(envh, OCI_HTYPE_ENV);
                    ]]
                )],
                [
                ORACLE_OCI_LDFLAGS="$oci_ldflags"
                ORACLE_OCI_LIBS="$oci_libs"
                oci_lib_found="yes"
                AC_MSG_RESULT([yes])
                ],
                [
                oci_lib_found="no"
                AC_MSG_RESULT([not found])
                ]
            )
        fi

        CPPFLAGS="$saved_CPPFLAGS"
        LDFLAGS="$saved_LDFLAGS"
        LIBS="$saved_LIBS"
    fi

    AC_MSG_CHECKING([for Oracle support])

    if test "$oci_header_found" = "yes" -a "$oci_lib_found" = "yes"; then

        AC_SUBST([ORACLE_OCI_VERSION])
        AC_SUBST([ORACLE_OCI_CFLAGS])
        AC_SUBST([ORACLE_OCI_LDFLAGS])
        AC_SUBST([ORACLE_OCI_LIBS])

        HAVE_ORACLE_OCI="yes"
    else
        HAVE_ORACLE_OCI="no"
    fi

    AC_MSG_RESULT([$HAVE_ORACLE_OCI])
])
