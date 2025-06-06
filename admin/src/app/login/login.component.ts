import { Component, OnInit } from "@angular/core";
import { Router, ActivatedRoute, NavigationEnd } from "@angular/router";
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
    confirmPassword: ''
  };

  // errorMessage = "";
  errorMessage: string = "";
  isLoading: boolean = false;
  // currentPath: string = "";
  // currentRoute: string = "";

  constructor(
    private adminService: AdminService,
    private router: Router,
    private toastr: ToastrService,
    private route: ActivatedRoute
  ) {
    if (localStorage.getItem("auth_token")) {
      this.router.navigate(["/dashboard"]);
    }

    // this.isChangePasswordRoute = this.router.url.includes('/change-password');

    // this.currentRoute = this.router.url;

    // this.router.events.subscribe((event) => {
    //   if (event instanceof NavigationEnd) {
    //     this.currentRoute = event.urlAfterRedirects;
    //   }
    // });
  }

  // isChangePasswordRoute(): boolean {
  //   return this.currentRoute.includes("change-password");
  // }

  // isLoginRoute(): boolean {
  //   return this.currentRoute.includes("login");
  // }

  ngOnInit(): void {







  }
  passwordVisible: boolean = false;
  permissionsData: any;
  permissionSlugs: any;
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




  login_redirect_slug: any;

  login(form: any) {
    if (form.valid) {
      this.isLoading = true;

      this.adminService.login(this.loginData).subscribe((response: any) => {
        if (response.success == 1) {
          console.log(response);
          this.adminService.setTokenData(response.data);
          this.adminService.userpermissions = response.data.permissions;

          this.permissionsData = this.adminService.getTokenData() && this.adminService.getTokenData().permissions; // p
          //
          if (this.permissionsData) {
            this.permissionSlugs = this.permissionsData.map(p => p.slug);
            if (this.permissionSlugs.includes('dashboard')) {
              this.login_redirect_slug = 'dashboard'
            }
            else {
              this.login_redirect_slug = this.permissionSlugs[0];
            }
            // console.log(this.permissionSlugs);
          }
          //
          this.toastr.success(response.message);
          setTimeout(() => {
            this.router.navigate([`${this.login_redirect_slug}`]);
          }, 500);
        } else {
          this.toastr.error(response.message);
        }
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      });
    } else {
      // console.log('Form is invalid.');
    }
  }
}
