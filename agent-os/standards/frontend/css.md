## CSS best practices

- **Consistent Methodology**: Apply and stick to the project's consistent CSS methodology (Tailwind, BEM, utility classes, CSS modules, etc.) across the entire project
- **Avoid Overriding Framework Styles**: Work with your framework's patterns rather than fighting against them with excessive overrides
- **Maintain Design System**: Establish and document design tokens (colors, spacing, typography) for consistency
- **Minimize Custom CSS**: Leverage framework utilities and components to reduce custom CSS maintenance burden
- **Performance Considerations**: Optimize for production with CSS purging/tree-shaking to remove unused styles

## Design Notes
- Active navigation item highlighted with indigo (primary color) background and text
- Hover states use subtle indigo tints
- Sidebar background uses slate-50 (light mode) and slate-900 (dark mode)
- Navigation items have clear visual feedback for active/inactive states
- User menu visually separated from navigation items with a subtle border
- Organization switcher appears above user profile with dropdown animation
- Organization dropdown has shadow and border for clear visual separation
- Current organization highlighted in dropdown with indigo accent
- Smooth transitions for sidebar open/close on mobile and dropdown interactions
