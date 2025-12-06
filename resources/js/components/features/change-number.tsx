import PhoneVerificationController from "@/actions/App/Http/Controllers/Auth/PhoneVerificationController";
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Form, usePage } from '@inertiajs/react';
import { useState } from "react";


export function ChangeNumber() {

  const { auth: { user: { phone } } } = usePage<{ auth: { user: { phone: string } } }>().props
  const [open, setOpen] = useState(false);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">Update Number</Button>
      </DialogTrigger>

      <DialogContent className="sm:max-w-[425px]">
        <Form
          {...PhoneVerificationController.updatePhone.form()}
          className="flex flex-col gap-6"
          onSuccess={() => setOpen(false)}
        >
          {({ processing, errors }) => (
            <>
              <DialogHeader>
                <DialogTitle>Change Phone Number</DialogTitle>
                <DialogDescription className="text-xs">
                  Enter your new phone number. You'll need to login to continue verification.
                </DialogDescription>
              </DialogHeader>

              <div className="grid gap-4">
                <div className="grid gap-3">
                  <Label htmlFor="phone">New Phone Number</Label>
                  <Input
                    id="phone"
                    name="phone"
                    defaultValue={phone}
                    placeholder="08012345678"
                    pattern="^0[7-9][0-1][0-9]{8}$"
                    maxLength={11}
                  />
                  {errors.phone && <span className="text-red-500 text-sm">{errors.phone}</span>}
                  <p className="text-xs text-muted-foreground">Enter a valid Nigerian phone number</p>
                </div>
              </div>

              <DialogFooter>
                <DialogClose asChild>
                  <Button type="button" variant="outline">Cancel</Button>
                </DialogClose>
                <Button type="submit" disabled={processing}>
                  {processing ? 'Updating...' : 'Update Phone'}
                </Button>
              </DialogFooter>
            </>
          )}
        </Form>
      </DialogContent>
    </Dialog>
  )
}
