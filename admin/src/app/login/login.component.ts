import { Component, OnInit } from "@angular/core";
import { Router } from "@angular/router";
import { AdminService } from "../_services/admin.service";
import { ToastrService } from "ngx-toastr";

@Component({
  selector: "app-login",
  templateUrl: "./login.component.html",
  styleUrls: ["./login.component.css"],
})
export class LoginComponent implements OnInit {
  loginData = {
    email: "",
    password: "",
  };

  // errorMessage = "";
  errorMessage: string = "";
  isLoading: boolean = false;

  constructor(private adminService: AdminService, private router: Router, private toastr: ToastrService) {
    if (localStorage.getItem('auth_token')) {
      router.navigate(['/dashboard']);
    }
  }

  ngOnInit(): void {
  }
  passwordVisible: boolean = false;

  togglePasswordVisibility() {
    this.passwordVisible = !this.passwordVisible;
  }

  // login(form: any): void {
  //   this.errorMessage = "";
  //   if (form.valid) {
  //     this.isLoading = true;

  //     // console.log('object submit btn');

  //     this.adminService.login(this.loginData).subscribe(
  //       (response: any) => {
  //         console.log(response);
  //         if (response.success == 1) {
  //           this.adminService.setTokenData(response.token);
  //           //  this.alertService.success("Logged In!");
  //           // this.toastr.success(response.message);
  //           setTimeout(() => {
  //             this.router.navigate(["/dashboard"]);
  //           }, 500);
  //         } else {
  //           // this.toastr.error(response.message);
  //           this.errorMessage =
  //             response.message || "Invalid email or password.";
  //         }

  //         setTimeout(() => {
  //           this.isLoading = false;
  //         }, 500);
  //       },
  //       (error) => {
  //         // this.toastr.error(error.error.message || 'An error occurred.');
  //         this.isLoading = false;
  //         // this.errorMessage = error.error.message || "Server error. Try again.";
  //       //      this.alertService.error(
  //       //   error.error.message || "An error occurred during login"
  //       // );
  //       }
  //     );
  //   } else {
  //     console.log("Form is invalid.");
  //     this.errorMessage = "Please enter valid credentials.";
  //   }
  // }

  onInputChange() {
    this.errorMessage = "";
  }


  login(form: any) {
    if (form.valid) {
      this.isLoading = true;

      this.adminService.login(this.loginData).subscribe(
        (response: any) => {
          if (response.success == 1) {
            this.adminService.setTokenData(response.data);
            this.toastr.success(response.message);
            setTimeout(() => {
              this.router.navigate(['/']);
            }, 500);
          } else {
            this.toastr.error(response.message);
          }
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        }
      )
    }
    else {
      // console.log('Form is invalid.');
    }
  }
}
