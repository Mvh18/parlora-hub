import type { SVGAttributes } from 'react';

/** The Parlora glyph: four voice bars of different heights. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <path d="M6 10v4" />
            <path d="M10 7v10" />
            <path d="M14 9v6" />
            <path d="M18 11v2" />
        </svg>
    );
}
