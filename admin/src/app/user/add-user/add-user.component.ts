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
  EditData = {
    name: '',
    email: '',
    //  password: '',
     contact_no : '',
     user_type_id: '',
     designation_id: ''
   };
 
  userId: number = null;


   userTypeList: Array<{ id: number; user_type: string }> = [];
   designationList: Array<{ id: number; designation: string }> = [];
 
   isLoading = false;
   errorMessage = '';
 
   constructor(private adminSevice: AdminService, private router: Router, private toastr: ToastrService, private route: ActivatedRoute) {}
 
   ngOnInit() {
     this.route.params.subscribe(params => {
          // console.log(params)
          this.userId = params["id"];
      });
      console.log(this.userId);

      this.adminSevice.get_user_details(this.userId).subscribe((res)=>{
        console.log(res);
        if(res.success){
          this.EditData = {
            name: res.details.name,
            email: res.details.email,
            // password: res,
            contact_no : res.details.contact_no,
            user_type_id: res.details.user_type_id,
            designation_id: res.details.designation_id
          };
        }else {
          this.toastr.error('User Not Found!');
          this.router.navigate(['/dashboard']);
        }
      })

     this.adminSevice.get_userTypeList().subscribe((res)=>{
       this.userTypeList = res.list;
      //  console.log(this.userTypeList);
     })
 
     this.adminSevice.get_designationList().subscribe((res)=>{
       this.designationList = res.list;
      //  console.log(this.designationList);
     })
   }
 
   onInputChange() {
     this.errorMessage = "";
   }
 
   onSubmit(form: any) {
     if (form.valid) {
       this.isLoading = true;
     }
 
     // for (let pair of (this.EditData as any).entries()) {
     //   console.log(`${pair[0]}:`, pair[1]);
     // }
 
     console.log(this.EditData);
 
     this.adminSevice.update_user_by_id(this.userId, this.EditData).subscribe((res)=>{
        if(res.success){
           this.toastr.success(res.message);
           this.router.navigate(['/users']);
           this.EditData = {
             name: '',
             email: '',
            //  password: '',
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
