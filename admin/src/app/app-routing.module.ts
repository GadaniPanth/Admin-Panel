import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";
import { LoginComponent } from "./login/login.component";
import { MasterComponent } from "./master/master.component";
import { DashboardComponent } from "./dashboard/dashboard.component";
import { ForgotPasswordComponent } from "./forgot-password/forgot-password.component";
import { AuthGuard } from "./auth/auth.guard";
import { RegisterUserComponent } from "./register-user/register-user.component";
import { AddFormsComponent } from "./add-forms/add-forms.component";
import { ListUserComponent } from "./user/list-user/list-user.component";
import { InquiryListComponent } from "./inquiry/inquiry-list/inquiry-list.component";
import { AddUserComponent } from "./user/add-user/add-user.component";
import { ListDynamicFormComponent } from "./dynamic-forms/list-dynamic-form/list-dynamic-form.component";
import { AddDynamicFormComponent } from "./dynamic-forms/add-dynamic-form/add-dynamic-form.component";
import { ChangePasswordComponent } from "./change-password/change-password.component";
import { MyProfileComponent } from "./my-profile/my-profile.component";

const routes: Routes = [
  // { path: "", redirectTo: "/login", pathMatch: "full" },
  { path: "login", component: LoginComponent },
  {
    // path: "forgot-password",
    path: "reset-password",
    component: ForgotPasswordComponent,
    data: { title: "Reset Password", tabName: "Reset Password" },
  },
  // {
  //   path: "change-password",
  //   component: ChangePasswordComponent,
  //   data: { title: "Change Password", tabName: "Change Password" },
  // },
  // {
  //   path: "register",
  //   component: RegisterUserComponent,
  //   // data: { title: 'Register User', tabName: 'Register User' },
  // },
  {
    path: "",
    component: MasterComponent,
    canActivate: [AuthGuard],
    children: [
      {
        path: "",
        redirectTo: "dashboard",
        pathMatch: "full",
      },
      {
        path: "change-password",
        component: ChangePasswordComponent,
        data: { title: "Change Password", tabName: "users" },
       },
      {
        path: "dashboard",
        component: DashboardComponent,
        data: { title: "Dashboard", tabName: "dashboard" },
      },
      {
        path: "register",
        component: AddUserComponent,
        data: { title: "Register User", tabName: "users" },
      },
      {
        path: "list-form",
        component: ListDynamicFormComponent,
        data: { title: "Forms", tabName: "List Form" },
      },
      {
        path: "create-form",
        component: AddDynamicFormComponent,
        data: { title: "Add Form", tabName: "List Form" },
      },
      {
        path: "edit-form/:id",
        component: AddDynamicFormComponent,
        data: { title: "Edit Form", tabName: "List Form" },
      },
      {
        path: "users",
        component: ListUserComponent,
        data: { title: "Users", tabName: "users" },
      },
      {
        path: "inquiry",
        component: InquiryListComponent,
        data: { title: "Inquiry", tabName: "inquiry" },
      },
      {
        path: "edit-user/:id",
        component: AddUserComponent,
        data: { title: "Edit User", tabName: "users" },
      },
      {
        path: "profile",
        component: MyProfileComponent,
        data: { title: "My Profile", tabName: "users" },
      },

      // For exmaple
      // {
      //   path: "category/list",
      //   component: CategoryListComponent,
      //   data: { title: "Category", tabName: "category" }
      // },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
