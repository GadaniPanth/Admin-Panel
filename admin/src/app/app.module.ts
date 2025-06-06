import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { MasterComponent } from './master/master.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InquiryListComponent } from './inquiry/inquiry-list/inquiry-list.component';
import { LoginComponent } from './login/login.component';
import { RegisterUserComponent } from './register-user/register-user.component';
import { AddFormsComponent } from './add-forms/add-forms.component';
import { FormsModule } from '@angular/forms';
import { HTTP_INTERCEPTORS, HttpClientModule } from '@angular/common/http';
import { ForgotPasswordComponent } from './forgot-password/forgot-password.component';
import { AddUserComponent } from './user/add-user/add-user.component';
import { ListUserComponent } from './user/list-user/list-user.component';
import { PagerService } from './_services/pager-service';
import { NgSelectModule } from '@ng-select/ng-select';
import { ToastrModule } from 'ngx-toastr';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AddDynamicFormComponent } from './dynamic-forms/add-dynamic-form/add-dynamic-form.component';
import { ListDynamicFormComponent } from './dynamic-forms/list-dynamic-form/list-dynamic-form.component';
import { DatePipe } from '@angular/common';
import { ChangePasswordComponent } from './change-password/change-password.component';
import { NgxDaterangepickerMd } from 'ngx-daterangepicker-material';
import { MyProfileComponent } from './my-profile/my-profile.component';
import { AuthInterceptor } from './auth/auth.interceptor';

@NgModule({
  declarations: [
    AppComponent,
    MasterComponent,
    DashboardComponent,
    InquiryListComponent,
    LoginComponent,
    RegisterUserComponent,
    AddFormsComponent,
    ForgotPasswordComponent,
    AddUserComponent,
    ListUserComponent,
    AddDynamicFormComponent,
    ListDynamicFormComponent,
    ChangePasswordComponent,
    MyProfileComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    FormsModule,
    HttpClientModule,
    NgSelectModule,
    BrowserAnimationsModule,
    NgxDaterangepickerMd.forRoot(),
    ToastrModule.forRoot({
      timeOut: 3000,
      positionClass: 'toast-bottom-right',
      preventDuplicates: true,
      maxOpened: 1,
      autoDismiss: true
    }),
  ],
  providers: [
    PagerService,
    DatePipe,
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,  // Register the interceptor
      multi: true                 // Allows multiple interceptors
    }
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
