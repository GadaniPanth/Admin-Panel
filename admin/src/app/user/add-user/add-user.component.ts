import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from "ngx-toastr";
import { AdminService } from 'src/app/_services/admin.service';

@Component({
  selector: 'app-add-user',
  templateUrl: './add-user.component.html',
  styleUrls: ['./add-user.component.css']
})
export class AddUserComponent implements OnInit {
  userId: number = null;
  UserData: any;
  isAdmin: boolean = false;
  // userPassword: string = '';
  formHead: string = 'Register User';
  // formBtn: string = 'Register';
  modules_list: any[] = [];
  permissions: any[]= [];

  userTypeList: Array<{ id: number; user_type: string }> = [];
  designationList: Array<{ id: number; designation: string }> = [];
 
  isLoading = false;
  errorMessage = '';
  moduleMsg: string = '';

  constructor(private adminService: AdminService, private router: Router, private toastr: ToastrService, private route: ActivatedRoute) {}

  ngOnInit() {
    this.isAdmin = this.adminService.getTokenData().user_type == "admin";
    this.route.params.subscribe(params => {
        // console.log(params)
        this.userId = params["id"];
    });
    // console.log(this.userId);

    if(this.userId){
      // this.permissions_data.user_id = this.userId;
      this.adminService.getModules().subscribe((res)=>{
        if(res.success){
          this.modules_list = res.list;
          // console.log(res.list);
        }else {
          this.moduleMsg = "No Modules Found!";
        }
      });


      this.formHead = 'Edit User';
      // this.formBtn = 'Submit';
      if(this.adminService.getTokenData().user_type_id == 1){
        // this.isAdmin = true;
        this.UserData = {
          name: '',
          email: '',
          password: '',
          // profile_pic: '',
          contact_no : '',
          user_type_id: '',
          others: '',
          designation_id: ''
        };
      }else {
        // this.isAdmin = false;
        this.UserData = {
          name: '',
          email: '',
          // password: '',
          // profile_pic: '',
          contact_no : '',
          user_type_id: '',
          others: '',
          designation_id: ''
        };
      }

      this.adminService.get_user_details(this.userId).subscribe((res)=>{
        // console.log(res);
        if(res.success){
          if(this.isAdmin){
            this.UserData = {
              name: res.details.name,
              email: res.details.email,
              password: '',
              // profile_pic: res.details.profile_pic,
              contact_no : res.details.contact_no,
              user_type_id: res.details.user_type_id,
              designation_id: res.details.designation_id
            };
            this.permissions = res.details.permissions;
            this.permissions = this.permissions.map((p, i) => ({
              ...p,
              can_add: p.can_add == "1",
              can_edit: p.can_edit == "1",
              can_view: p.can_view == "1",
              can_export: p.can_export == "1",
              can_delete: p.can_delete == "1"
            })).sort((a, b) => Number(a.module_id) - Number(b.module_id));
            // this.adminService.get_user_password(this.userId).subscribe((resPass)=>{
            //   if(resPass.success){
            //     // this.toastr.success(resPass.password)


            //     // console.log(this.permissions);
            //   }else {
            //     // this.toastr.error(resPass.message)
            //   }
            // });
          }else {
            this.UserData = {
              name: res.details.name,
              email: res.details.email,
              // password: res.details.password,
              // profile_pic : res.details.profile_pic,
              contact_no : res.details.contact_no,
              user_type_id: res.details.user_type_id,
              designation_id: res.details.designation_id
            };
          }
        }else {
          this.toastr.error('User Not Found!');
          this.router.navigate(['/dashboard']);
        }
      })
    }else {
      // this.isAdmin = true;
      this.UserData = {
        name: '',
        email: '',
        password: '',
        profile_pic: '',
        contact_no : '',
        user_type_id: '',
        others: '',
        designation_id: ''
      };
    }

    this.adminService.get_userTypeList().subscribe((res)=>{
      this.userTypeList = res.list;
    //  console.log(this.userTypeList);
    })

    this.adminService.get_designationList().subscribe((res)=>{
      this.designationList = res.list;
      // this.designationList.push({id: null, designation: 'Others'});
    //  console.log(this.designationList);
    })
  }

  
  isOthers: boolean = false;
  onInputChange() {
    this.errorMessage = "";
    this.isOthers = this.UserData.designation_id === 'null';
  }

  onSubmit(form: any) {
    if (form.valid) {
      this.isLoading = true;
    }

    // for (let pair of (this.UserData as any).entries()) {
    //   console.log(`${pair[0]}:`, pair[1]);
    // }

  //  console.log(this.UserData);
    if(this.userId){
      this.adminService.update_user_by_id(this.userId, this.UserData).subscribe((res)=>{
        if(res.success){
            this.toastr.success(res.message);
            this.router.navigate(['/users']);
            if(this.isAdmin){
              this.UserData = {
                name: '',
                email: '',
                password: '',
                // profile_pic: '',
                contact_no : '',
                user_type_id: '',
                others: '',
                designation_id: ''
              };
            }
            else {
              this.UserData = {
                name: '',
                email: '',
                // password: '',
                // profile_pic : '',
                contact_no : '',
                user_type_id: '',
                others: '',
                designation_id: ''
              };
            }
            this.isLoading = false;
          }else {
            this.toastr.error(res.message);
            this.isLoading = false;
          }
      })
    }else {
       this.adminService.create_user(this.UserData).subscribe((res)=>{
        if(res.success){
            this.toastr.success(res.message);
            this.router.navigate(['/users']);
            this.UserData = {
              name: '',
              email: '',
              password: '',
              // profile_pic: '',
              contact_no : '',
              user_type_id: '',
              others: '',
              designation_id: ''
            };
            this.isLoading = false;
          }else {
            this.toastr.error(res.message);
            this.isLoading = false;
          }
      })
    }
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

  changePermissions() {
    // this.permissions = this.permissions
    // .map(p => ({
    //   ...p,
    //   can_add: p.can_add ? 1 : 0,
    //   can_edit: p.can_edit ? 1 : 0,
    //   can_view: p.can_view ? 1 : 0,
    //   can_export: p.can_export ? 1 : 0,
    //   can_delete: p.can_delete ? 1 : 0
    // }))

    // const permissions_data = {
    //   user_id: this.userId,
    //   permissions: this.permissions
    // };

     const permissions_data = {
      user_id: this.userId.toString(),
      permissions: JSON.stringify(
        this.permissions.map(p => ({
          module_id: p.module_id.toString(),
          can_add: p.can_add ? "1" : "0",
          can_edit: p.can_edit ? "1" : "0",
          can_view: p.can_view ? "1" : "0",
          can_export: p.can_export ? "1" : "0",
          can_delete: p.can_delete ? "1" : "0"
        }))
      )
    };

    this.adminService.managePermission(permissions_data).subscribe((res)=>{
      if(res.success){
        this.toastr.success(res.message);
      }else {
        this.toastr.success(res.message);
      }
    });
    // console.log('Updated Permissions:', this.permissions);
  }
}
