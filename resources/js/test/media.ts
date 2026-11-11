/**
 * jsdom has no matchMedia. setup.ts installs a stub whose `matches` reads
 * this object, so a test can switch to the mobile layout.
 */
export const mediaQuery = { matches: false };
