# Console Dashboard - Trishaki

A beautiful static dashboard with glassmorphism cards for quick navigation to different services and tools.

## Features

- 🎨 **Modern Design**: Glassmorphism cards with colorful gradients on black background
- 🚀 **Static & Fast**: Pure HTML/CSS with no JavaScript or backend dependencies
- 📱 **Responsive**: Works perfectly on desktop, tablet, and mobile devices
- ✨ **Smooth Animations**: Beautiful hover effects with glow and lift animations
- 🔗 **Mixed Navigation**: Links to both external subdomains and local folders

## Current Cards

### External Subdomains
- **📝 Forms** → `https://forms.trishaki.com` - Form management system
- **🔐 Vaulto** → `https://vaulto.trishaki.com` - Secure vault service

### Local Folders
- **🎓 Interns** → `/interns/` - Internship management
- **🚀 Projects** → `/projects/` - Project portfolio
- **👤 Accounts** → `/accounts/` - User account management
- **🛠️ Tools** → `/tools/` - Development tools and utilities

## Installation

1. **Upload files** to your web server at `console.trishaki.com`
2. **Access the dashboard** at `https://console.trishaki.com`

That's it! No configuration needed.

## File Structure

```
console.trishaki.com/
├── index.html          # Main dashboard page
└── README.md           # This documentation
```

## Customization

### Adding New Cards

To add a new card, edit `index.html`:

1. **Add HTML for the card:**
```html
<a href="YOUR_URL" class="card card-YOURNAME" target="_blank">
    <div class="card-header">
        <div class="card-icon">🎯</div>
        <div class="card-title">your name</div>
    </div>
</a>
```

2. **Add CSS theme:**
```css
.card-YOURNAME .card-icon {
    background: linear-gradient(135deg, #COLOR1, #COLOR2);
    box-shadow: 0 8px 25px rgba(R, G, B, 0.4), 0 0 20px rgba(R, G, B, 0.2);
}

.card-YOURNAME:hover .card-icon {
    box-shadow: 0 12px 35px rgba(R, G, B, 0.6), 0 0 30px rgba(R, G, B, 0.3);
}
```

### Changing Card URLs

Simply update the `href` attribute in the card's `<a>` tag:

```html
<!-- Change from local folder to external subdomain -->
<a href="https://newservice.trishaki.com" class="card card-tools" target="_blank">
```

### Color Themes

Each card has a unique gradient and glow effect:
- **Forms**: Blue gradient (4facfe → 00f2fe)
- **Vaulto**: Green gradient (43e97b → 38f9d7)
- **Interns**: Pink-yellow gradient (fa709a → fee140)
- **Projects**: Pink gradient (f093fb → f5576c)
- **Accounts**: Purple gradient (667eea → 764ba2)
- **Tools**: Teal gradient (a8edea → fed6e3)

## Design Features

- **Black Background**: Dramatic contrast for card visibility
- **Glassmorphism**: Semi-transparent cards with blur effects
- **Glow Effects**: Each card has colored shadows and hover glows
- **Smooth Animations**: 0.4s cubic-bezier transitions
- **Responsive Grid**: Auto-adjusting layout for all screen sizes

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## License

MIT License - Feel free to modify and use as needed!

---

**Console Dashboard for Trishaki Services** 🚀