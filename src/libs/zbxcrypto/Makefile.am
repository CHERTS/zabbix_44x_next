## Process this file with automake to produce Makefile.in

noinst_LIBRARIES = libzbxcrypto.a

libzbxcrypto_a_SOURCES = \
	md5.c \
	sha256crypt.c \
	base64.c \
	tls.c \
	tls.h \
	tls_tcp.h \
	tls_tcp_active.h \
	tls_version.c

libzbxcrypto_a_CFLAGS = @TLS_CFLAGS@
