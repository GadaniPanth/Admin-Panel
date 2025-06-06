import { Component, OnInit } from "@angular/core";
import { AdminService } from "../_services/admin.service";
// import { error } from "console";
import { ToastrService } from "ngx-toastr";
import { MasterComponent } from "../master/master.component";

@Component({
  selector: "app-my-profile",
  templateUrl: "./my-profile.component.html",
  styleUrls: ["./my-profile.component.css"],
})
export class MyProfileComponent implements OnInit {
  userData = {
    name: "",
    email: "",
    //  password: '',
    contact_no: "",
    profile_pic: "",
    //  user_type_id: '',
    designation: '',
    designation_id: '',
    others: ''
  };

  userId: number;
  errorMessage = "";
  constructor(
    public adminService: AdminService,
    private toastr: ToastrService,
    private master: MasterComponent
  ) {
    // console.log(this.adminService.getTokenData().user_id);
     this.getUser();
  }

  isOthers: boolean = false;

  onInputChange() {
    this.errorMessage = "";
    this.isOthers = this.userData.designation_id === 'null';
  }
  designationList: Array<{ id: number; designation: string }> = [];

  getUser() {
    this.adminService
      .get_user_details(this.adminService.getTokenData().user_id)
      .subscribe((res) => {
        this.userData.profile_pic = res.details.profile_pic ? res.details.profile_pic : 'assets/images/default-user.jpg';
        this.userData.name = res.details.name;
        this.userData.email = res.details.email;
        this.userData.contact_no = res.details.contact_no;
        this.userData.designation = res.details.designation;
        this.userData.designation_id = res.details.designation_id;
        this.userId = res.details.user_id;
      });
    // setTimeout(()=>{
    //   this.userData.name = this.master.activeUser.name;
    //   this.userData.email = this.master.activeUser.email;
    //   this.userData.contact_no = this.master.activeUser.contact_no;
    //   this.userId = this.master.activeUser.user_id;
    // },100)

    // console.log(this.userId);
  }

  ngOnInit() {
    this.getUser();
     this.adminService.get_designationList().subscribe((res)=>{
      this.designationList = res.list;
    //  console.log(this.designationList);
    })
  }

  updateProfile() {
    const formData = new FormData();

    formData.append('user_id', this.userId.toString());
    formData.append('name', this.userData.name);
    formData.append('email', this.userData.email);
    formData.append('contact_no', this.userData.contact_no);
    formData.append('designation_id', this.userData.designation_id);
    formData.append('others', this.userData.others);

    // If a new file is selected
    if (this.selectedFile) {
      formData.append('profile_pic', this.selectedFile);
    }

    console.log(this.userData);

    this.adminService.update_user_by_id(this.userId, formData).subscribe((res) => {
      if (res.success) {
        this.toastr.success(res.message);
        this.master.getActiveUser();
      } else {
        this.toastr.error(res.message);
      }
    });
  }


  restrictNonNumeric(event: KeyboardEvent) {
    const regex = /[0-9]/;
    if (!regex.test(event.key)) {
      event.preventDefault();
    }
  }

  imagePreview: string | ArrayBuffer = '';

  selectedFile: File | null = null;

  changedImage(event: Event) {
    const input = event.target as HTMLInputElement;

    if (input.files && input.files[0]) {
      this.selectedFile = input.files[0];
      const reader = new FileReader();

      reader.onload = () => {
        this.imagePreview = reader.result!;
      };

      reader.readAsDataURL(this.selectedFile);
    }
  }


  removeImage(event: Event){
    this.imagePreview = null;
    this.userData.profile_pic = null;
  }

}
