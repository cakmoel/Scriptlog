---
name: Epicurean Dark
colors:
  surface: '#121414'
  surface-dim: '#121414'
  surface-bright: '#38393a'
  surface-container-lowest: '#0c0f0f'
  surface-container-low: '#1a1c1c'
  surface-container: '#1e2020'
  surface-container-high: '#282a2b'
  surface-container-highest: '#333535'
  on-surface: '#e2e2e2'
  on-surface-variant: '#d1c5b4'
  inverse-surface: '#e2e2e2'
  inverse-on-surface: '#2f3131'
  outline: '#9a8f80'
  outline-variant: '#4e4639'
  surface-tint: '#e9c176'
  primary: '#e9c176'
  on-primary: '#412d00'
  primary-container: '#c5a059'
  on-primary-container: '#4e3700'
  inverse-primary: '#775a19'
  secondary: '#c8c6c5'
  on-secondary: '#313030'
  secondary-container: '#474746'
  on-secondary-container: '#b7b5b4'
  tertiary: '#c9c6c5'
  on-tertiary: '#313030'
  tertiary-container: '#a7a5a4'
  on-tertiary-container: '#3c3b3b'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffdea5'
  primary-fixed-dim: '#e9c176'
  on-primary-fixed: '#261900'
  on-primary-fixed-variant: '#5d4201'
  secondary-fixed: '#e5e2e1'
  secondary-fixed-dim: '#c8c6c5'
  on-secondary-fixed: '#1c1b1b'
  on-secondary-fixed-variant: '#474746'
  tertiary-fixed: '#e5e2e1'
  tertiary-fixed-dim: '#c9c6c5'
  on-tertiary-fixed: '#1c1b1b'
  on-tertiary-fixed-variant: '#474646'
  background: '#121414'
  on-background: '#e2e2e2'
  surface-variant: '#333535'
typography:
  display-lg:
    fontFamily: EB Garamond
    fontSize: 72px
    fontWeight: '500'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: EB Garamond
    fontSize: 48px
    fontWeight: '500'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: EB Garamond
    fontSize: 32px
    fontWeight: '500'
    lineHeight: '1.2'
  headline-md:
    fontFamily: EB Garamond
    fontSize: 32px
    fontWeight: '500'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Manrope
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-caps:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.1em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  section-gap: 80px
  container-max: 1280px
  gutter: 24px
  margin-mobile: 20px
  margin-desktop: 60px
---

## Brand & Style

This design system is built upon a foundation of culinary excellence and premium hospitality. The brand personality is authoritative yet welcoming, evocative of high-end gastronomy and artisanal craftsmanship. 

The visual style blends **Minimalism** with **Glassmorphism**. It utilizes expansive dark surfaces to create a sense of intimacy and prestige, while translucent layers provide a modern, airy feel that prevents the dark palette from feeling heavy. The emotional response should be one of "sophisticated comfort"—appealing to users who value quality, tradition, and modern refinement.

## Colors

The palette is anchored by a "Culinary Gold" primary accent, used sparingly to guide the eye toward key actions and highlights. The background hierarchy utilizes deep blacks and charcoals to create depth.

- **Primary (Gold):** Represents prestige and quality. Used for primary call-to-actions, icons, and active states.
- **Secondary (Charcoal):** Used for surface-level containers and UI elements that need to sit slightly above the background.
- **Tertiary (Black):** The base canvas color, providing a high-contrast backdrop for photography and typography.
- **Neutral (Off-White):** Employed for primary text and labels to ensure maximum legibility without the harshness of pure white.

## Typography

This design system employs a high-contrast typographic pairing to signal premium quality. 

**EB Garamond** is used for headlines and display text. Its classical proportions and elegant serifs evoke a sense of tradition and editorial authority. **Manrope** provides a functional, modern counter-balance for body copy and UI labels, ensuring clarity and readability across all device sizes. Use `label-caps` for eyebrows and small navigation elements to add a rhythmic, structured feel to the layout.

## Layout & Spacing

The layout philosophy follows a **Fixed Grid** model for content containment, ensuring a curated editorial feel. 

- **Grid:** A 12-column grid is used for desktop layouts, transitioning to a 4-column grid for mobile.
- **Rhythm:** A strict 8px baseline grid maintains vertical rhythm. 
- **Safe Areas:** Generous section gaps (80px+) are used to separate content blocks, reinforcing the minimalist, premium aesthetic and allowing photography to breathe.
- **Adaptation:** On mobile, margins reduce to 20px, and large display type scales down significantly to maintain visual hierarchy without overwhelming the viewport.

## Elevation & Depth

Depth is achieved through **Glassmorphism** and **Tonal Layering** rather than traditional heavy shadows.

- **The Stats Bar:** Elements like the information counter should use a semi-transparent background (e.g., `rgba(255, 255, 255, 0.05)`) with a `backdrop-filter: blur(12px)`.
- **Borders:** Instead of shadows, use low-contrast, 1px solid borders in a slightly lighter shade than the background (`rgba(255, 255, 255, 0.1)`) to define element boundaries.
- **Overlays:** Hero sections use a subtle radial gradient overlay (dark at the edges, slightly lighter in the center) to focus attention on the central message and improve text contrast against busy imagery.

## Shapes

The shape language is sophisticated and "Soft-Rounded." While the overall layout is architectural and structured, UI components utilize a `0.5rem` (8px) corner radius to feel approachable and modern. 

Avoid sharp 0px corners which can feel too aggressive, or full pill-shapes which may appear too casual for a premium culinary brand. Circles are reserved exclusively for icon backgrounds and avatar containers to create focal points.

## Components

- **Primary Buttons:** Solid Gold background with dark charcoal text. No border. These should feel substantial and be the clearest call to action.
- **Secondary Buttons:** Transparent background with a 1px solid Gold or White border. Used for secondary navigation or alternative actions.
- **Cards:** Utilize the glassmorphism style defined in the Elevation section. Backgrounds should be dark and translucent to let the underlying imagery or brand colors peak through.
- **Lists & Stats:** Use thin, vertical separators between items (as seen in the hero stats bar) to maintain a clean, organized structure.
- **Input Fields:** Dark background (darker than the surface color) with a subtle bottom border or 1px stroke. Focus states should transition the border color to Primary Gold.
- **Chips/Badges:** Use the `label-caps` typography style with a subtle background tint to categorize content without distracting from headlines.