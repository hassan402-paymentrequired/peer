import * as React from 'react';

import { cn } from '@/lib/utils';
import { Label } from './label';
import { Separator } from './separator';

const FieldGroup = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(({ className, ...props }, ref) => (
    <div ref={ref} className={cn('space-y-6', className)} {...props} />
));
FieldGroup.displayName = 'FieldGroup';

const FieldSet = React.forwardRef<HTMLFieldSetElement, React.HTMLAttributes<HTMLFieldSetElement>>(({ className, ...props }, ref) => (
    <fieldset ref={ref} className={cn('space-y-4', className)} {...props} />
));
FieldSet.displayName = 'FieldSet';

const Field = React.forwardRef<
    HTMLDivElement,
    React.HTMLAttributes<HTMLDivElement> & {
        orientation?: 'horizontal' | 'vertical';
    }
>(({ className, orientation = 'vertical', ...props }, ref) => (
    <div
        ref={ref}
        className={cn(
            'space-y-2',
            orientation === 'horizontal' && 'flex items-center justify-between space-x-4 space-y-0',
            className,
        )}
        {...props}
    />
));
Field.displayName = 'Field';

const FieldContent = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(({ className, ...props }, ref) => (
    <div ref={ref} className={cn('flex-1 space-y-1', className)} {...props} />
));
FieldContent.displayName = 'FieldContent';

const FieldLabel = React.forwardRef<
    React.ElementRef<typeof Label>,
    React.ComponentPropsWithoutRef<typeof Label>
>(({ className, ...props }, ref) => <Label ref={ref} className={cn('text-sm font-medium', className)} {...props} />);
FieldLabel.displayName = 'FieldLabel';

const FieldTitle = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(({ className, ...props }, ref) => (
    <div ref={ref} className={cn('text-sm font-medium leading-none', className)} {...props} />
));
FieldTitle.displayName = 'FieldTitle';

const FieldDescription = React.forwardRef<HTMLParagraphElement, React.HTMLAttributes<HTMLParagraphElement>>(
    ({ className, ...props }, ref) => <p ref={ref} className={cn('text-sm text-muted-foreground', className)} {...props} />,
);
FieldDescription.displayName = 'FieldDescription';

const FieldSeparator = React.forwardRef<
    React.ElementRef<typeof Separator>,
    React.ComponentPropsWithoutRef<typeof Separator> & {
        children?: React.ReactNode;
    }
>(({ className, children, ...props }, ref) => (
    <div className="relative">
        <div className="absolute inset-0 flex items-center">
            <Separator ref={ref} className={className} {...props} />
        </div>
        {children && (
            <div className="relative flex justify-center text-xs uppercase">
                <span className="bg-background px-2 text-muted-foreground">{children}</span>
            </div>
        )}
    </div>
));
FieldSeparator.displayName = 'FieldSeparator';

export { Field, FieldContent, FieldDescription, FieldGroup, FieldLabel, FieldSeparator, FieldSet, FieldTitle };
