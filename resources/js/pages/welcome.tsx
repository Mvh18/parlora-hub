import { Head, Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/hooks/use-trans';
import { dashboard, login } from '@/routes';

// A literal URL: Wayfinder does not generate the register route when
// HUB_REGISTRATION_ENABLED is false.
const registerUrl = '/register';

export default function Welcome() {
    const { auth, features } = usePage().props;
    const { t } = useTrans();

    return (
        <>
            <Head title={t('welcome.head')} />
            <div className="flex min-h-svh flex-col bg-canvas px-[clamp(20px,4vw,48px)] py-6">
                <header className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-2.5">
                        <AppLogo />
                    </div>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>
                                    {t('nav.dashboard')}
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost">
                                    <Link href={login()}>
                                        {t('auth.login.submit')}
                                    </Link>
                                </Button>
                                {features.registration && (
                                    <Button asChild>
                                        <Link href={registerUrl}>
                                            {t('welcome.register')}
                                        </Link>
                                    </Button>
                                )}
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 items-center py-12">
                    <div className="grid w-full items-center gap-10 md:grid-cols-[3fr_2fr]">
                        <div className="flex flex-col gap-4">
                            <h1 className="text-display font-bold text-ink">
                                {t('welcome.title')}
                            </h1>
                            <p className="max-w-prose text-body text-ink-muted">
                                {t('welcome.intro')}
                            </p>
                        </div>

                        <div aria-hidden="true" className="flex flex-col gap-3">
                            <p className="max-w-xs self-start bubble-bl-sm bg-surface px-5 py-4 text-body-sm text-ink">
                                {t('welcome.sample_question')}
                            </p>
                            <p className="max-w-xs self-end bubble-br bg-accent-tint px-5 py-4 text-body-sm text-ink">
                                {t('welcome.sample_answer')}
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
