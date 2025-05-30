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

const routes: Routes = [
  // { path: "", redirectTo: "/login", pathMatch: "full" },
  { path: "login", component: LoginComponent },
  {
    path: "forgot-password",
    component: ForgotPasswordComponent,
    data: { title: "Forgot Password", tabName: "Forgot Password" },
  },
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
        path: "dashboard",
        component: DashboardComponent,
        data: { title: "Dashboard", tabName: "dashboard" },
      },
      {
        path: "register",
        component: RegisterUserComponent,
        data: { title: "Register User", tabName: "Register User" },
      },
      {
        path: "list-form",
        component: ListDynamicFormComponent,
        data: { title: "List Form", tabName: "List Form" },
      },
      {
        path: "create-form",
        component: AddFormsComponent,
        data: { title: "Create Form", tabName: "Create Form" },
      },
      {
        path: "edit-form/:id",
        component: AddFormsComponent,
        data: { title: "Edit Form", tabName: "Edit Form" },
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
        data: { title: "Edit User", tabName: "Edit User" },
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
