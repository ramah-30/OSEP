# i18n (Internationalization) Guide

## Overview

The OSEP platform uses **i18next** for multi-language support. The system automatically syncs with user language preferences and includes translation files for:
- **English** (en)
- **Kiswahili** (sw)
- **Français** (fr)

## How It Works

1. **User preferences** → Stored in database (user.preferences.locale)
2. **App initialization** → useLanguage hook syncs preference with i18next
3. **Settings change** → When user changes language in Settings → Preferences, i18n updates instantly
4. **Component translation** → Use `useTranslation()` hook in React components

## Using Translations in Components

### Basic Usage

```jsx
import { useTranslation } from 'react-i18next'

function MyComponent() {
  const { t } = useTranslation()

  return <button>{t('common.save')}</button>
}
```

Output:
- English: "Save"
- Kiswahili: "Hifadhi"
- French: "Enregistrer"

### With Variables

Add to translation file:
```json
{
  "messages": {
    "welcome": "Welcome, {{name}}"
  }
}
```

In component:
```jsx
<p>{t('messages.welcome', { name: 'John' })}</p>
```

## Translation File Structure

Translation files are organized in:
```
src/i18n/locales/
├── en.json      (English)
├── sw.json      (Kiswahili)
└── fr.json      (French)
```

Each file has this structure:
```json
{
  "category": {
    "key": "translation",
    "another": "translation"
  }
}
```

### Current Categories

- **common** - Buttons, actions (Save, Cancel, Delete, etc.)
- **nav** - Navigation items
- **auth** - Login/Register forms
- **settings** - Settings pages
- **events** - Events-related terms
- **finance** - Finance terms (Invoice, Payment, etc.)
- **ai** - AI Assistant terms
- **messages** - Success/error messages

## Adding New Translations

### Step 1: Add to English file (`src/i18n/locales/en.json`)
```json
{
  "myFeature": {
    "title": "My Feature Title",
    "description": "Feature description"
  }
}
```

### Step 2: Add same keys to Swahili (`src/i18n/locales/sw.json`)
```json
{
  "myFeature": {
    "title": "Jina la Sifa Yangu",
    "description": "Maelezo ya sifa"
  }
}
```

### Step 3: Add same keys to French (`src/i18n/locales/fr.json`)
```json
{
  "myFeature": {
    "title": "Mon titre de fonctionnalité",
    "description": "Description de la fonctionnalité"
  }
}
```

### Step 4: Use in component
```jsx
import { useTranslation } from 'react-i18next'

function MyFeature() {
  const { t } = useTranslation()

  return (
    <div>
      <h1>{t('myFeature.title')}</h1>
      <p>{t('myFeature.description')}</p>
    </div>
  )
}
```

## Best Practices

1. **Use descriptive keys** - `auth.forgotPassword` is better than `forgot_pwd`
2. **Group related translations** - Keep related strings in same category
3. **Keep translations consistent** - Same term should use same key everywhere
4. **Test all languages** - Always verify translations in Settings → Preferences
5. **Keep keys organized** - Sort alphabetically within each category

## Debugging

### Check current language
```jsx
import { useTranslation } from 'react-i18next'

function Debug() {
  const { i18n } = useTranslation()
  console.log('Current language:', i18n.language)
  return null
}
```

### Check if translation exists
```jsx
const { t } = useTranslation()
console.log(t('myKey.subKey')) // Returns key if not found
```

### Force language change (testing)
```jsx
const { i18n } = useTranslation()
i18n.changeLanguage('sw') // Change to Swahili
```

## Future Enhancement: Dynamic Translation Files

To avoid hardcoding translations and use a translation API or service:

```javascript
// Example: Load from translation service
const backendResources = await fetch('/api/translations/en').then(r => r.json())
i18n.addResourceBundle('en', 'translation', backendResources, true, true)
```

## Performance Notes

- Translation files are bundled at build time (no runtime fetch)
- Language switching is instant (in-memory cache)
- Fallback to English if translation missing
- localStorage persists user preference across sessions
