import { Component, OnInit } from '@angular/core';
import { AdminService } from '../_services/admin.service';
import { Router } from '@angular/router';
import { ToastrService } from "ngx-toastr";

@Component({
  selector: 'app-register-user',
  templateUrl: './register-user.component.html',
  styleUrls: ['./register-user.component.css']
})
export class RegisterUserComponent implements OnInit {
  RegisterData = {
    name: '',
    email: '',
    password: '',
    contact_no : '',
    user_type_id: '',
    designation_id: ''
  };

  userTypeList: Array<{ id: number; user_type: string }> = [];
  designationList: Array<{ id: number; designation: string }> = [];

  isLoading = false;
  errorMessage = '';

  constructor(private adminSevice: AdminService, private router: Router, private toastr: ToastrService) {}

  ngOnInit() {
    this.adminSevice.get_userTypeList().subscribe((res)=>{
      this.userTypeList = res.list;
      // console.log(this.userTypeList);
    })

    this.adminSevice.get_designationList().subscribe((res)=>{
      this.designationList = res.list;
      // console.log(this.designationList);
    })
  }

  onInputChange() {
    this.errorMessage = "";
  }

  register(form: any) {
    if (form.valid && this.isLoading == false) {
      this.isLoading = true;
    }

    // for (let pair of (this.RegisterData as any).entries()) {
    //   console.log(`${pair[0]}:`, pair[1]);
    // }

    // console.log(this.RegisterData);

    this.adminSevice.create_user(this.RegisterData).subscribe((res)=>{
       if(res.success){
          this.toastr.success(res.message);
          this.router.navigate(['/users']);
          this.RegisterData = {
            name: '',
            email: '',
            password: '',
            contact_no : '',
            user_type_id: '',
            designation_id: ''
          };
          this.isLoading = false;
        }else {
          this.toastr.error(res.message);
          this.isLoading = false;
        }
    })
  }

  passwordVisible: boolean = false;
  togglePasswordVisibility() {
    this.passwordVisible = !this.passwordVisible;
  }

  restrictNonNumeric(event: KeyboardEvent) {
    const regex = /[0-9]/;
    if (!regex.test(event.key)) {
      event.preventDefault();
    }
  }
}
