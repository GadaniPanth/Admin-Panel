import { Component } from "@angular/core";
import { NgForm } from "@angular/forms";
import { ActivatedRoute, Router } from "@angular/router";
import { AdminService } from "../_services/admin.service";
import { ToastrService } from "ngx-toastr";

@Component({
  selector: "app-forgot-password",
  templateUrl: "./forgot-password.component.html",
  styleUrls: ["./forgot-password.component.css"],
})
export class ForgotPasswordComponent {
  email: string = "";
  errorMessage: string = "";
  isReset: boolean = false;
  str: string = "email address";
  token: string = "";

  constructor(
    private route: ActivatedRoute,
    private adminService: AdminService,
    private toastr: ToastrService,
    private router: Router
  ) {
    this.route.queryParams.subscribe((params) => {
      this.isReset = params["token"] ? true : false;
      this.token = params["token"] ? decodeURIComponent(params["token"]) : "";
      console.log(this.token)
      if (this.isReset) {
        this.str = "password";
      }
    });
  }

  onInputChange(): void {
    this.errorMessage = "";
  }

  onSubmit(form: NgForm): void {
    // if (form.valid) {
    //   console.log(this.email);
    // } else {
    //   this.errorMessage = "";
    // }

    // console.log(form.value.email);

    this.adminService
      .forgot_password({ toEmail: form.value.email })
      .subscribe((res: any) => {
        if (res.success) {
          this.toastr.success(res.message);
          this.router.navigate(["/login"]);
        } else {
          this.toastr.error(res.message);
        }
      });
  }

  resetPass = {
    password: "",
    confirmPassword: "",
  };

  newPasswordVisible: boolean = false;
  confirmPasswordVisible: boolean = false;
  passwordsMismatch: boolean = false;

  toggleNewPasswordVisibility(): void {
    this.newPasswordVisible = !this.newPasswordVisible;
  }

  toggleConfirmPasswordVisibility(): void {
    this.confirmPasswordVisible = !this.confirmPasswordVisible;
  }

  validatePasswords(): void {
    this.passwordsMismatch =
      this.resetPass.password !== this.resetPass.confirmPassword;
  }

  onResetSubmit(form: any): void {
    // if (form.valid && !this.passwordsMismatch) {
    //   console.log("Password reset successful:", this.resetPass.password);
    // }
    const requestData = {
      token: encodeURIComponent(this.token),
      password: this.resetPass.password,
      confirm_password: this.resetPass.confirmPassword,
    };

    this.adminService.update_password(requestData).subscribe((res) => {
      if (res.success) {
        this.toastr.success(res.message);
      } else {
        this.toastr.error(res.message);
      }
      this.router.navigate(['/login']);
    });
  }
}
