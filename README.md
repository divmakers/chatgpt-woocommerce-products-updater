# 🤖 GPT Product Description Updater for WooCommerce

Automatically enhance your WooCommerce product descriptions using OpenAI's GPT-3.5 API. This plugin helps store owners maintain fresh, engaging, and SEO-friendly product descriptions with minimal effort.

## 🌟 Features

- 🔄 Automatic product description updates using GPT-3.5
- ⚡ Manual update option for individual products
- 🏷️ Automatic tagging of updated products
- 📊 Status tracking for each product
- ⏱️ Minute-by-minute cron job scheduling
- 🔒 Secure API key management
- 🎯 Custom meta box for single product updates
- 📋 Product status column in admin view

## 🚀 Installation

1. Download the plugin files
2. Upload the plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to "GPT Product Updater" in your WordPress admin menu
5. Enter your OpenAI API key in the settings

## ⚙️ Configuration

### API Key Setup
1. Go to "GPT Product Updater" in your admin menu
2. Enter your OpenAI API key
3. Save changes

### Auto-Update Settings
- Enable/disable automatic updates using the radio buttons
- Updates run every minute for non-processed products
- Products are automatically tagged with 'gpt-updated' after processing

## 📖 Usage

### Bulk Updates
Click the "Update Descriptions" button in the plugin settings page to initiate bulk updates.

### Single Product Updates
1. Open any product for editing
2. Locate the "GPT Product Description Updater" meta box
3. Click "Update Current Product Description"

### Monitoring Updates
- Check the "GPT Status" column in your products list
- Look for the 'gpt-updated' tag on processed products
- View individual product meta for detailed status

## 🔧 Technical Details

### API Configuration
- Model: GPT-3.5-turbo
- Temperature: 0.7
- Max tokens: 150
- Allowed HTML tags: `<img>, <b>, <h1>, <h2>, <h3>, <p>`

### Cron Job
- Runs every minute
- Processes one product per cycle
- Automatically tags processed products

## 🛡️ Security

- WordPress nonce verification
- Capability checking for admin actions
- Sanitized inputs and escaped outputs
- Secure API key storage

## 🔍 Troubleshooting

### Common Issues
1. **API Key Invalid**
   - Verify your OpenAI API key
   - Ensure key has proper permissions

2. **Updates Not Running**
   - Check WP-Cron is functioning
   - Verify server permissions
   - Check error logs

3. **Description Not Updating**
   - Ensure product has initial description
   - Check API response in logs
   - Verify product permissions

## 📝 Changelog

### Version 1.0
- Initial release
- Basic GPT integration
- Auto-update functionality
- Manual update options
- Status tracking
- Product tagging

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is licensed under the GPL v2 or later.

## 👥 Credits

Developed by Ayda

---

<p align="center">
For support, feature requests, or bug reports, please open an issue on the repository.
</p> 
