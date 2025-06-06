import { HttpClient, HttpHeaders } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { NavigationEnd, Router } from "@angular/router";
import { Observable } from "rxjs";
import { filter } from "rxjs/operators";
import { environment } from "src/environments/environment";

@Injectable({
  providedIn: "root",
})
export class AdminService {
  constructor(private http: HttpClient, private router: Router) {
    // permission
    this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe((event: NavigationEnd) => {
        if (!event.urlAfterRedirects.includes("/login")) {
          this.userpermissions = this.getTokenData().permissions;
        }
      });

    //
  }

  userpermissions: any; // p
  private apiUrl = environment.api;
  private TOKEN_KEY = "auth_token";

  private getHttpOptions(type: string = "json") {
    let headers = new HttpHeaders();
    let token = this.getTokenData() && this.getTokenData().token;

    if (type == "json") {
      headers = headers.set("Content-Type", "application/json");
    }
    // console.log('Token data:', this.getTokenData());

    if (token) {
      headers = headers.set("Authorization", token);
    }

    return { headers };
  }

  setTokenData(tokenData: string) {
    localStorage.setItem(this.TOKEN_KEY, JSON.stringify(tokenData));
  }

  getTokenData() {
    return JSON.parse(localStorage.getItem(this.TOKEN_KEY));
  }

  // getTokenData() {
  //   const tokenStr = localStorage.getItem(this.TOKEN_KEY);
  //   if (!tokenStr) return null;

  //   try {
  //     return JSON.parse(tokenStr);
  //   } catch (e) {
  //     console.error("Invalid token in localStorage", e);
  //     return null;
  //   }
  // }

  login(data: any): Observable<any> {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/login/with_password",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  removeTokenData() {
    localStorage.removeItem(this.TOKEN_KEY);
  }

  // login(data: any): Observable<any> {
  //   data.from_app = true;
  //   // console.log('object login')
  //   return this.http
  //     .post<any>(
  //       this.apiUrl + "/Admin_services/login",
  //       JSON.stringify(data),
  //       this.getHttpOptions()
  //     )
  //     .pipe();
  // }

  changePassword(data: any): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + "admin_services/change_password",
      data,
      this.getHttpOptions("formdata")
    );
  }

  createForm(data: any): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + "admin_services/create_form",
      data,
      this.getHttpOptions("formdata")
    );
  }

  getCounters(): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + "admin_services/get_counters",
      this.getHttpOptions()
    );
  }

  getModules(): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + "admin_services/modules_list",
      this.getHttpOptions()
    );
  }

  get_userTypeList(): Observable<any> {
    return this.http.get<any>(
      this.apiUrl + "admin_services/userTypeList",
      this.getHttpOptions()
    );
  }

  get_designationList(): Observable<any> {
    return this.http.get<any>(
      this.apiUrl + "admin_services/designationList",
      this.getHttpOptions()
    );
  }

  create_user(data: any) {
    return this.http.post<any>(
      this.apiUrl + "admin_services/create_user",
      data,
      this.getHttpOptions("formdata")
    );
  }

  delete_user(data: any) {
    return this.http.post<any>(
      this.apiUrl + `admin_services/delete_user`,
      data,
      this.getHttpOptions()
    );
  }

  get_users_list(data: any) {
    return this.http.post<any>(
      this.apiUrl + "admin_services/usersList",
      data,
      this.getHttpOptions()
    );
  }

  get_user_details(id: number) {
    const data = { user_id: id };
    return this.http.post<any>(
      this.apiUrl + `admin_services/userDetails`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  // get_user_password(id: number) {
  //   const data = { user_id: id };
  //   return this.http.post<any>(
  //     this.apiUrl + `admin_services_panth/get_user_password`,
  //     data,
  //     this.getHttpOptions("formdata")
  //   );
  // }

  update_user_by_id(id: number, data: any) {
    data.user_id = id;
    return this.http.post<any>(
      this.apiUrl + `admin_services/update_user`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  update_password(data: any) {
    return this.http.post<any>(
      this.apiUrl + `admin_services/update_password`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  forgot_password(data: any) {
    data.from_app = true;
    return this.http.post<any>(
      this.apiUrl + `admin_services/reset_link`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  // inquiryList(data: any): Observable<any> {
  //   return this.http.get<any>(this.apiUrl + "/admin_services/inquiryList", JSON.stringify(data), this.getHttpOptions()).pipe();
  // }

  formList(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(
      this.apiUrl + `admin_services/get_forms`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  getForm(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(
      this.apiUrl + `admin_services/get_form_by_id`,
      data,
      this.getHttpOptions()
    );
  }

  editForm(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(
      this.apiUrl + `admin_services/edit_form`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  deleteForm(data: any): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + `admin_services/delete_form`,
      data,
      this.getHttpOptions()
    );
  }

  changeStatusForm(data: any): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + `admin_services/change_form_status`,
      data,
      this.getHttpOptions()
    );
  }

  managePermission(data: any): Observable<any> {
    console.log(data);
    return this.http.post<any>(
      this.apiUrl + `admin_services/managePermissions`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  changeStatusUser(data: any): Observable<any> {
    return this.http.post<any>(
      this.apiUrl + `admin_services/change_user_status`,
      data,
      this.getHttpOptions("formdata")
    );
  }

  userTypeCounter(data: any) {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/userTypeCounter",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  inquiryList(data: any): Observable<any> {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/inquiryList",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  inquiryTypeList(data: any): Observable<any> {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/inquiry_type",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  inquiryStatusCounter(data: any) {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/inquiryStatusCounter",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  inquiryStatusList(data: any) {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/inquiryStatus",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  inquiryHistoryListing(data: any) {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/inquirHistory",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  change_status_inquiry(data: any) {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/inquiry_follwup",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  dashboardCounters(data: any) {
    data.from_app = true;
    return this.http
      .post<any>(
        this.apiUrl + "admin_services/dashboardCounters",
        JSON.stringify(data),
        this.getHttpOptions()
      )
      .pipe();
  }

  // for example

  // categoryList(data: any): Observable<any> {
  //   data.from_app = true;
  //   return this.http.post<any>(this.apiUrl + 'admin_services/categoryList', JSON.stringify(data), this.getHttpOptions()).pipe();
  // }
  // categoryStatusToggle(data: any): Observable<any> {
  //   data.from_app = true;
  //   return this.http.post<any>(this.apiUrl + 'admin_services/categoryStatusToggle', JSON.stringify(data), this.getHttpOptions()).pipe();
  // }
  // saveCategory(data: FormData): Observable<any> {
  //   return this.http.post<any>(this.apiUrl + 'admin_services/categorySave', data, this.getHttpOptions('formdata')).pipe();
  // }

  // if formdata
  // saveProduct(data: FormData) {
  //   return this.http.post<any>(this.apiUrl + 'admin_services/productSave', data, this.getHttpOptions('formdata')).pipe();
  // }
  // if formdata

  // for example
}
