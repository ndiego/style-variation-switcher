# Style Variation Switcher

> Note: This README was generated using AI (Anthropic's Claude) via Cursor Composer.

A highly experimental WordPress plugin that allows visitors to switch between block theme style variations on the frontend. This plugin was created entirely using AI prompts with Cursor Composer as a proof of concept.

## Description

Style Variation Switcher adds a floating color palette switcher to your WordPress site, allowing visitors to preview and switch between different color variations of your block theme. It's specifically designed to work with theme.json style variations, focusing on color-only variations.

### Features

- Floating color palette switcher UI
- Automatically detects and displays color-only variations
- Shows preview swatches using theme colors
- Accessible design with screen reader support
- Automatic font preloading for variations
- Clean, minimal interface

### Technical Notes

- Built for WordPress block themes using theme.json
- Uses WordPress Theme JSON Resolver API
- Follows WordPress coding standards
- Built with vanilla JavaScript and jQuery
- Fully responsive design

## Requirements

- WordPress 6.0 or higher
- A block theme with style variations
- PHP 7.4 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/style-variation-switcher`
2. Activate the plugin through the WordPress plugins screen
3. Your theme must have style variations defined in theme.json

## Usage

Once activated, the plugin automatically adds a floating color switcher to your site. The switcher will only show variations that are color-only (no typography or other styling changes).

## Development Notes

This plugin was created as an experimental proof of concept using AI-assisted development:
- Developed entirely using Cursor Composer prompts
- Built iteratively through AI conversation
- Focused on WordPress best practices and coding standards
- Demonstrates potential of AI-assisted WordPress development

## Limitations

As this is a proof of concept:
- Only works with block themes
- Only shows color-only variations
- Minimal configuration options
- May not work with all theme structures
- Experimental and not recommended for production use

## Credits

- Created using Cursor Composer AI
- Developed by Nick Diego
- Inspired by WordPress style variation system

## License

GPL v2 or later

## Disclaimer

This is an experimental plugin created as a proof of concept. It is not recommended for production use without thorough testing and validation. 