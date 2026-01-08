# Frontend Components Context

**Parent Context:** [Frontend Root](../../AGENTS.md)

## PURPOSE
Reusable UI components built with React, Tailwind CSS, and Shadcn UI.

## CRITICAL RULES
1.  **Composition:** Prefer composition over inheritance. Small, focused components are better.
2.  **Shadcn:** We use Shadcn UI. Do not reinvent basic primitives (Buttons, Inputs, Dialogs) unless absolutely necessary.
3.  **Tailwind:** Use utility classes for styling. Avoid custom CSS files or `style={{}}` props.
4.  **Types:** All components MUST be typed with TypeScript interfaces/types.
5.  **Accessibility:** Ensure all interactive elements are keyboard accessible and have aria-labels where needed.

## STRUCTURE
- `ui/`: Core Shadcn primitives.
- `shared/`: Components used across multiple features.
- `[feature]/`: Feature-specific components (if not collocated in `app/`).

## ANTI-PATTERNS
- Hardcoding colors/values (use Tailwind config/variables).
- deeply nested ternary operators for rendering logic.
- "God components" that do too much.
