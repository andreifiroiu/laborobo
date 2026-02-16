import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import { Fragment, useState } from 'react';

function SiblingDropdown({ item }: { item: BreadcrumbItemType }) {
    const [open, setOpen] = useState(false);

    if (!item.siblings || item.siblings.length <= 1) {
        return null;
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    className="text-muted-foreground hover:text-foreground ml-1 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-sm transition-colors"
                    aria-label="Switch to sibling"
                >
                    <ChevronsUpDown className="h-3 w-3" />
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-64 p-1">
                <div className="max-h-60 overflow-y-auto">
                    {item.siblings.map((sibling) => (
                        <Link
                            key={sibling.href}
                            href={sibling.href}
                            onClick={() => setOpen(false)}
                            className={`block rounded-sm px-2 py-1.5 text-sm transition-colors ${
                                sibling.href === item.href
                                    ? 'bg-accent text-accent-foreground font-medium'
                                    : 'hover:bg-accent hover:text-accent-foreground'
                            }`}
                        >
                            {sibling.title}
                        </Link>
                    ))}
                </div>
            </PopoverContent>
        </Popover>
    );
}

export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs: BreadcrumbItemType[];
}) {
    return (
        <>
            {breadcrumbs.length > 0 && (
                <Breadcrumb>
                    <BreadcrumbList>
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            return (
                                <Fragment key={index}>
                                    <BreadcrumbItem>
                                        {isLast ? (
                                            <span className="inline-flex items-center">
                                                <BreadcrumbPage>
                                                    {item.title}
                                                </BreadcrumbPage>
                                                <SiblingDropdown item={item} />
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center">
                                                <BreadcrumbLink asChild>
                                                    <Link href={item.href}>
                                                        {item.title}
                                                    </Link>
                                                </BreadcrumbLink>
                                                <SiblingDropdown item={item} />
                                            </span>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && <BreadcrumbSeparator />}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
