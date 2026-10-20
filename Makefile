### Makefile
### Automates everything in the project.
###
### Author: Nathan Campos <nathan@innoveworkshop.com>

# Tools
RM     = rm -f
LN     = ln -s
MKDIR  = mkdir -p
CURL   = curl
MAKE   = make

# Paths
BINDIR = ./bin
HTDOCS = ./
PHLOG  = ./phlog

.PHONY: all build images phlog

all: build

build: images phlog
	$(CURL) -o "$(HTDOCS)/robots.txt" 'https://raw.githubusercontent.com/ai-robots-txt/ai.robots.txt/refs/heads/main/robots.txt'

images:
	$(BINDIR)/update-compat-images.pl

phlog:
	$(BINDIR)/build-phlog-index.pl

$(HTDOCS)/robots.txt:
	$(CURL) -o "$(HTDOCS)/robots.txt" 'https://raw.githubusercontent.com/ai-robots-txt/ai.robots.txt/refs/heads/main/robots.txt'
