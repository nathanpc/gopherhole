#!/usr/bin/env ruby

require 'ascii-image'

# All that you have to do is call the build(width) method with the maximum width
# of characters that the ANSI art should be.

# Local image.
ascii = ASCII_Image.new("~/my_image.jpg")
ascii.build(20)

# Fetch image from the internet.
ascii = ASCII_Image.new("http://www.levihackwith.com/wp-content/uploads/2011/10/github-logo.png")
ascii.build(60)
