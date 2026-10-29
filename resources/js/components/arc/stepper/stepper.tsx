"use client";

import type { KeyboardEvent, ReactNode } from 'react';
import styles from './stepper.module.css';

export interface StepperStep {
    id: string;
    label: string;
    description?: string;
    error?: string;
}

interface StepperProps {
    steps: StepperStep[];
    current: number;
    onStepSelect?: (index: number) => void;
    details?: 'all' | 'current';
    label?: string;
    completeLabel?: string;
    className?: string;
}

function statusFor(index: number, current: number, step: StepperStep): 'complete' | 'current' | 'upcoming' | 'error' {
    if (step.error) {
        return 'error';
    }
    if (index < current) {
        return 'complete';
    }
    if (index === current) {
        return 'current';
    }
    return 'upcoming';
}

/**
 * Position indicator for the customer wizard. Renders the same
 * documented props as the Arc stepper (steps, current, onStepSelect).
 * Installed manually because the registry was unreachable from this network.
 */
export function Stepper({
    steps,
    current,
    onStepSelect,
    details = 'all',
    label = 'Progress',
    completeLabel = 'All steps complete',
    className,
}: StepperProps) {
    const count = steps.length;
    const active = Math.min(Math.max(Math.round(current), 0), count);
    const done = active >= count;
    const now = done ? undefined : steps[active];

    function onKeyDown(event: KeyboardEvent<HTMLOListElement>): void {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) {
            return;
        }
        const targets = Array.from(event.currentTarget.querySelectorAll<HTMLButtonElement>('button[data-reachable]'));
        const at = targets.indexOf(event.target as HTMLButtonElement);
        if (at < 0) {
            return;
        }
        event.preventDefault();
        let next = at;
        if (event.key === 'Home') {
            next = 0;
        } else if (event.key === 'End') {
            next = targets.length - 1;
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
            next = Math.max(0, at - 1);
        } else {
            next = Math.min(targets.length - 1, at + 1);
        }
        targets[next]?.focus();
    }

    const interactive = Boolean(onStepSelect);

    function markerContent(index: number, status: string): ReactNode {
        if (status === 'complete') {
            return <span aria-hidden="true">✓</span>;
        }
        if (status === 'error') {
            return <span aria-hidden="true">!</span>;
        }
        return <span aria-hidden="true">{index + 1}</span>;
    }

    return (
        <nav className={[styles.root, className].filter(Boolean).join(' ')} aria-label={label}>
            <ol className={styles.list} onKeyDown={interactive ? onKeyDown : undefined}>
                {steps.map((step, index) => {
                    const status = statusFor(index, active, step);
                    const clickable = interactive && index < active;
                    const detail = step.error ?? (details === 'all' || index === active ? step.description : undefined);
                    const content = (
                        <>
                            <span className={styles.marker}>{markerContent(index, status)}</span>
                            <span className={styles.text}>
                                <span className={styles.label}>
                                    {step.label}
                                    <span className={styles.srOnly}>
                                        {status === 'complete' ? ', Completed' : status === 'error' ? ', Error' : status === 'upcoming' ? ', Not started' : ''}
                                    </span>
                                </span>
                                {detail ? <span className={step.error ? styles.error : styles.description}>{detail}</span> : null}
                            </span>
                        </>
                    );
                    return (
                        <li key={step.id} className={styles.item} data-status={status}>
                            <span className={styles.connector} aria-hidden="true">
                                <span className={styles.fill} />
                            </span>
                            {interactive ? (
                                <button
                                    type="button"
                                    className={styles.head}
                                    data-clickable={clickable || undefined}
                                    data-reachable={index <= active || undefined}
                                    aria-current={index === active ? 'step' : undefined}
                                    aria-disabled={clickable ? undefined : true}
                                    tabIndex={clickable ? undefined : -1}
                                    onClick={clickable ? () => onStepSelect?.(index) : undefined}
                                >
                                    {content}
                                </button>
                            ) : (
                                <span className={styles.head} aria-current={index === active ? 'step' : undefined}>
                                    {content}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
            <span className={styles.srOnly} aria-live="polite">
                {now ? `Step ${active + 1} of ${count}: ${now.label}` : completeLabel}
            </span>
        </nav>
    );
}

export default Stepper;
