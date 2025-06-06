import { Component, OnInit } from "@angular/core";
import { NgForm } from "@angular/forms";
import { AdminService } from "../_services/admin.service";
import { ToastrService } from "ngx-toastr";
import { Router } from "@angular/router";

@Component({
  selector: "app-change-password",
  templateUrl: "./change-password.component.html",
  styleUrls: ["./change-password.component.css"],
})
export class ChangePasswordComponent implements OnInit {
  loginData = {
    // email: "",
    user_id: null,
    oldPassword: "",
    password: "", // new password
    confirmPassword: "",
  };

  passwordVisible = false;
  newPasswordVisible = false;
  confirmPasswordVisible = false;

  constructor(public adminService: AdminService, private toastr: ToastrService, private router: Router) {}

  ngOnInit() {}

  togglePasswordVisibility(): void {
    this.passwordVisible = !this.passwordVisible;
  }

  toggleNewPasswordVisibility(): void {
    this.newPasswordVisible = !this.newPasswordVisible;
  }

  toggleConfirmPasswordVisibility(): void {
    this.confirmPasswordVisible = !this.confirmPasswordVisible;
  }

  onSubmit(form: NgForm): void {
    // this.errorMessage = "";
    // this.successMessage = "";

    if (this.loginData.password !== this.loginData.confirmPassword) {
      this.toastr.error("Passwords do not match.");
      return;
    }

    const requestData = {
      // email: this.loginData.email,
      user_id: this.adminService.getTokenData().user_id,
      old_password: this.loginData.oldPassword,
      new_password: this.loginData.password,
      confirm_password: this.loginData.confirmPassword,
    };

    this.adminService.changePassword(requestData).subscribe((res) => {
        if (res.success) {
          this.toastr.success(res.message);
          this.router.navigate(['/profile']);
          form.resetForm();
        } else {
          this.toastr.error(res.message);
        }
  });
  }
}
