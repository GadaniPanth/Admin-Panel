import { HttpClient, HttpHeaders } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { Router } from "@angular/router";
import { Observable } from "rxjs";
import { environment } from "src/environments/environment";

@Injectable({
  providedIn: "root",
})
export class AdminService {
  constructor(private http: HttpClient, private router: Router) { }

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
    return this.http.post<any>(this.apiUrl + 'admin_services/login/with_password', JSON.stringify(data), this.getHttpOptions()).pipe();
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

  createForm(data: any): Observable<any> {
    return this.http.post<any>(this.apiUrl + "/Admin_services_panth/create_form", data, this.getHttpOptions("formdata"));
  }

  get_userTypeList(): Observable<any> {
    return this.http.get<any>(this.apiUrl + "/admin_services/userTypeList", this.getHttpOptions());
  }

  get_designationList(): Observable<any> {
    return this.http.get<any>(this.apiUrl + "/admin_services/designationList", this.getHttpOptions());
  }

  create_user(data: any) {
    return this.http.post<any>(this.apiUrl + "/Admin_services_panth/create_user", data, this.getHttpOptions("formdata"));
  }

  delete_user(data: any) {
    return this.http.post<any>(this.apiUrl + `/Admin_services_panth/delete_user`, data, this.getHttpOptions());
  }

  get_users_list() {
    return this.http.get<any>(this.apiUrl + "/admin_services/usersList", this.getHttpOptions());
  }

  get_user_details(id: number) {
    const data = { user_id: id };
    return this.http.post<any>(this.apiUrl + `/admin_services/userDetails`, data, this.getHttpOptions("formdata"));
  }

  update_user_by_id(id:number, body: any) {
    body.user_id = id;
    return this.http.post<any>(this.apiUrl + `/Admin_services_yash/update_user/`, body, this.getHttpOptions("formdata"));
  }

  // inquiryList(data: any): Observable<any> {
  //   return this.http.get<any>(this.apiUrl + "/admin_services/inquiryList", JSON.stringify(data), this.getHttpOptions()).pipe();
  // }

  formList(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + `/Admin_services_panth/get_forms`, data, this.getHttpOptions("formdata"));
  }

  getForm(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + `/Admin_services_panth/get_form_by_id/`, data, this.getHttpOptions());
  }

  editForm(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + `/Admin_services_panth/edit_form/`, data, this.getHttpOptions("formdata"));
  }

  deleteForm(data: any): Observable<any> {
    return this.http.post<any>(this.apiUrl + `/Admin_services_panth/delete_form`, data, this.getHttpOptions());
  }

  inquiryList(data: any): Observable<any> {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + 'admin_services/inquiryList', JSON.stringify(data), this.getHttpOptions()).pipe();
  }

  inquiryStatusList(data: any) {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + 'admin_services/inquiryStatus', JSON.stringify(data), this.getHttpOptions()).pipe();
  }

  inquiryHistoryListing(data: any) {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + 'admin_services/inquirHistory', JSON.stringify(data), this.getHttpOptions()).pipe();
  }


  change_status_inquiry(data: any) {
    data.from_app = true;
    return this.http.post<any>(this.apiUrl + 'admin_services/inquiry_follwup', JSON.stringify(data), this.getHttpOptions()).pipe();
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
